<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterInitRequest;
use App\Http\Requests\RegisterAccountRequest;
use App\Http\Requests\RegisterVerifyRequest;
use App\Http\Requests\RegisterResendOtpRequest;
use App\Models\RegistrationSession;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Card;
use App\Models\JwtSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    // -------------------------------------------------------------------------
    // Step 1: Select user type (working_professional / service_professional)
    // -------------------------------------------------------------------------

    public function init(RegisterInitRequest $request): JsonResponse
    {
        // Create a new registration session with a unique token
        $session = RegistrationSession::create([
            'token'      => Str::uuid()->toString(),
            'user_type'  => $request->user_type,
            'expires_at' => now()->addHours(2),
        ]);

        return response()->json([
            'status'             => true,
            'message'            => 'Card type selected. Please proceed to account setup.',
            'registration_token' => $session->token,
            'user_type'          => $session->user_type,
        ]);
    }

    // -------------------------------------------------------------------------
    // Step 2: Submit account details and send OTP
    // -------------------------------------------------------------------------

    public function account(RegisterAccountRequest $request): JsonResponse
    {
        $session = RegistrationSession::where('token', $request->registration_token)->first();

        // Check if session is expired
        if ($session->isExpired()) {
            return response()->json([
                'status'  => false,
                'message' => 'Registration session has expired. Please start again.',
            ], 410);
        }

        // Generate a 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Update session with account details and OTP
        $session->update([
            'full_name'      => $request->full_name,
            'country_code'   => $request->country_code,
            'mobile_number'  => $request->country_code . $request->mobile_number,
            'otp'            => $otp,
            'otp_expires_at' => now()->addMinutes(10),
            'otp_verified'   => 0,
        ]);

        // TODO: Send OTP via WhatsApp API
        // For now, log the OTP for development/testing purposes
        Log::info('Registration OTP for ' . $session->mobile_number . ': ' . $otp);

        // Mask the mobile number for the response (e.g. 88****88)
        $maskedNumber = $this->maskMobileNumber($request->mobile_number);

        return response()->json([
            'status'         => true,
            'message'        => 'OTP sent to your WhatsApp number.',
            'masked_number'  => $maskedNumber,
            'otp_expires_in' => 600, // seconds
        ]);
    }

    // -------------------------------------------------------------------------
    // Step 3: Verify OTP and complete registration
    // -------------------------------------------------------------------------

    public function verify(RegisterVerifyRequest $request): JsonResponse
    {
        $session = RegistrationSession::where('token', $request->registration_token)->first();

        // Check if session is expired
        if ($session->isExpired()) {
            return response()->json([
                'status'  => false,
                'message' => 'Registration session has expired. Please start again.',
            ], 410);
        }

        // Check if OTP has expired
        if ($session->isOtpExpired()) {
            return response()->json([
                'status'  => false,
                'message' => 'OTP has expired. Please request a new one.',
            ], 422);
        }

        // Validate OTP
        if ($session->otp !== $request->otp) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid OTP. Please try again.',
            ], 422);
        }

        // Check all required data is present
        if (!$session->full_name || !$session->mobile_number || !$session->user_type) {
            return response()->json([
                'status'  => false,
                'message' => 'Incomplete registration data. Please start again.',
            ], 422);
        }

        // Create the user
        $user = User::create([
            'name'              => $session->full_name,
            'email'             => null,
            'password'          => bcrypt(Str::random(16)), // random password, login via OTP
            'registration_type' => 'professional',
            'user_type'         => $session->user_type,
            'mobile_number'     => $session->mobile_number,
            'status'            => 1,
        ]);

        // Create a default workspace for the user
        $workspace = Workspace::create([
            'user_id' => $user->id,
            'name'    => $session->full_name . "'s Workspace",
            'status'  => 'active',
        ]);

        // Create the initial card
        Card::create([
            'user_id'      => $user->id,
            'workspace_id' => $workspace->id,
            'name'         => $session->full_name,
            'status'       => 'active',
            'step_count'   => 1,
        ]);

        // Mark OTP as verified and clean up session
        $session->update(['otp_verified' => 1]);
        $session->delete();

        // Generate JWT token and log session
        $token = auth('api')->login($user);
        $this->storeSession($user, $token, $request);

        return response()->json([
            'status'  => true,
            'message' => 'Registration successful. Welcome to Card.vu!',
            'token'   => $token,
        ]);
    }

    // -------------------------------------------------------------------------
    // Resend OTP
    // -------------------------------------------------------------------------

    public function resendOtp(RegisterResendOtpRequest $request): JsonResponse
    {
        $session = RegistrationSession::where('token', $request->registration_token)->first();

        // Check if session is expired
        if ($session->isExpired()) {
            return response()->json([
                'status'  => false,
                'message' => 'Registration session has expired. Please start again.',
            ], 410);
        }

        if (!$session->mobile_number) {
            return response()->json([
                'status'  => false,
                'message' => 'No mobile number found. Please complete account details first.',
            ], 422);
        }

        // Generate a new 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $session->update([
            'otp'            => $otp,
            'otp_expires_at' => now()->addMinutes(10),
            'otp_verified'   => 0,
        ]);

        // TODO: Send OTP via WhatsApp API
        Log::info('Resend Registration OTP for ' . $session->mobile_number . ': ' . $otp);

        return response()->json([
            'status'         => true,
            'message'        => 'OTP resent to your WhatsApp number.',
            'otp_expires_in' => 600, // seconds
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function maskMobileNumber(string $number): string
    {
        $len = strlen($number);
        if ($len <= 4) {
            return str_repeat('*', $len);
        }
        return substr($number, 0, 2) . str_repeat('*', $len - 4) . substr($number, -2);
    }

    private function storeSession(User $user, string $token, $request): void
    {
        $ttlMinutes = auth('api')->factory()->getTTL();
        JwtSession::create([
            'user_id'      => $user->id,
            'token'        => $token,
            'token_hash'   => hash('sha256', $token),
            'device_name'  => $request->header('X-Device-Name'),
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'expires_at'   => now()->addMinutes($ttlMinutes),
            'last_used_at' => now(),
            'is_active'    => 1,
        ]);
    }
}
