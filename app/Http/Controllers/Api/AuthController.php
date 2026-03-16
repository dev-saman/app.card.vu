<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterProfessionalRequest;
use App\Http\Requests\RegisterBrandRequest;
use App\Models\User;
use App\Models\Brand;
use App\Models\JwtSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    // -------------------------------------------------------------------------
    // POST /api/auth/register/professional
    // -------------------------------------------------------------------------
    public function registerProfessional(RegisterProfessionalRequest $request): JsonResponse
    {
        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'mobile_number'     => $request->mobile_number,
            'password'          => $request->password, // auto-hashed by User model cast
            'registration_type' => 'professional',
            'status'            => 1,
            'timezone'          => $request->timezone,
            'country'           => $request->country,
            'profile_picture'   => $request->profile_picture,
        ]);

        $token = auth('api')->login($user);
        $this->storeSession($user, $token, request());

        return response()->json([
            'success' => true,
            'message' => 'Professional account created successfully.',
            'data'    => [
                'user'       => $this->userResource($user),
                'token'      => $token,
                'token_type' => 'Bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ],
        ], 201);
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/register/brand
    // -------------------------------------------------------------------------
    public function registerBrand(RegisterBrandRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // 1. Create user
            $user = User::create([
                'name'              => $request->name,
                'email'             => $request->email,
                'mobile_number'     => $request->mobile_number,
                'password'          => $request->password,
                'registration_type' => 'brand',
                'status'            => 1,
                'timezone'          => $request->timezone ?? $request->brand_timezone,
                'country'           => $request->country ?? $request->brand_country,
                'profile_picture'   => $request->profile_picture,
            ]);

            // 2. Create brand linked to the new user
            $brand = Brand::create([
                'user_id'    => $user->id,
                'brand_name' => $request->brand_name,
                'url'        => $request->brand_url,
                'category'   => $request->brand_category,
                'owner_name' => $request->brand_owner_name ?? $request->name,
                'country'    => $request->brand_country,
                'timezone'   => $request->brand_timezone,
                'currency'   => $request->brand_currency,
                'status'     => 1,
            ]);

            DB::commit();

            $token = auth('api')->login($user);
            $this->storeSession($user, $token, request());

            return response()->json([
                'success' => true,
                'message' => 'Brand account created successfully.',
                'data'    => [
                    'user'       => $this->userResource($user),
                    'brand'      => $this->brandResource($brand),
                    'token'      => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Store the JWT token in jwt_sessions for multi-device tracking.
     */
    private function storeSession(User $user, string $token, Request $request): void
    {
        $ttlMinutes = auth('api')->factory()->getTTL(); // default 60 min

        JwtSession::create([
            'user_id'      => $user->id,
            'token'        => $token,
            'token_hash'   => hash('sha256', $token),
            'device_name'  => $request->header('X-Device-Name'),   // optional client header
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
            'expires_at'   => now()->addMinutes($ttlMinutes),
            'last_used_at' => now(),
            'is_active'    => 1,
        ]);
    }

    // -------------------------------------------------------------------------
    // Resource helpers — shape the response objects
    // -------------------------------------------------------------------------

    private function userResource(User $user): array
    {
        return [
            'id'                => $user->id,
            'name'              => $user->name,
            'email'             => $user->email,
            'mobile_number'     => $user->mobile_number,
            'registration_type' => $user->registration_type,
            'status'            => $user->status,
            'timezone'          => $user->timezone,
            'country'           => $user->country,
            'profile_picture'   => $user->profile_picture,
            'created_at'        => $user->created_at,
        ];
    }

    private function brandResource(Brand $brand): array
    {
        return [
            'id'         => $brand->id,
            'brand_name' => $brand->brand_name,
            'url'        => 'card.vu/' . $brand->url,
            'slug'       => $brand->url,
            'category'   => $brand->category,
            'owner_name' => $brand->owner_name,
            'country'    => $brand->country,
            'timezone'   => $brand->timezone,
            'currency'   => $brand->currency,
            'status'     => $brand->status,
            'created_at' => $brand->created_at,
        ];
    }
}
