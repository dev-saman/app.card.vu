<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Brand;
use App\Models\JwtSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    // -------------------------------------------------------------------------
    // POST /api/auth/register/professional
    // -------------------------------------------------------------------------
    public function registerProfessional(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile_number'   => ['required', 'string', 'max:20'],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
            'timezone'        => ['nullable', 'string', 'max:100'],
            'country'         => ['nullable', 'string', 'max:100'],
            'profile_picture' => ['nullable', 'url', 'max:500'],
        ], [
            'name.required'          => 'Full name is required.',
            'email.required'         => 'Email address is required.',
            'email.email'            => 'Please enter a valid email address.',
            'email.unique'           => 'This email is already registered.',
            'mobile_number.required' => 'Mobile number is required.',
            'password.required'      => 'Password is required.',
            'password.min'           => 'Password must be at least 8 characters.',
            'password.confirmed'     => 'Passwords do not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

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
        $this->storeSession($user, $token, $request);

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
    public function registerBrand(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // --- User account fields ---
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile_number'    => ['required', 'string', 'max:20'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'timezone'         => ['nullable', 'string', 'max:100'],
            'country'          => ['nullable', 'string', 'max:100'],
            'profile_picture'  => ['nullable', 'url', 'max:500'],

            // --- Brand fields ---
            'brand_name'       => ['required', 'string', 'max:255'],
            'brand_url'        => ['required', 'string', 'max:255', 'unique:brands,url', 'regex:/^[a-z0-9\-]+$/'],
            'brand_category'   => ['required', 'string', 'max:100'],
            'brand_owner_name' => ['nullable', 'string', 'max:255'],
            'brand_country'    => ['nullable', 'string', 'max:100'],
            'brand_timezone'   => ['nullable', 'string', 'max:100'],
            'brand_currency'   => ['nullable', 'string', 'max:10'],
        ], [
            'name.required'           => 'Full name is required.',
            'email.required'          => 'Email address is required.',
            'email.email'             => 'Please enter a valid email address.',
            'email.unique'            => 'This email is already registered.',
            'mobile_number.required'  => 'Mobile number is required.',
            'password.required'       => 'Password is required.',
            'password.min'            => 'Password must be at least 8 characters.',
            'password.confirmed'      => 'Passwords do not match.',
            'brand_name.required'     => 'Brand name is required.',
            'brand_url.required'      => 'Brand URL is required.',
            'brand_url.unique'        => 'This brand URL is already taken.',
            'brand_url.regex'         => 'Brand URL may only contain lowercase letters, numbers, and hyphens.',
            'brand_category.required' => 'Brand category is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

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
            $this->storeSession($user, $token, $request);

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
    // POST /api/auth/login
    // -------------------------------------------------------------------------
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        /** @var User $user */
        $user = auth('api')->user();

        if (! $user->status) {
            auth('api')->logout();
            return response()->json([
                'success' => false,
                'message' => 'Your account is inactive. Please contact support.',
            ], 403);
        }

        $this->storeSession($user, $token, $request);

        $data = [
            'user'       => $this->userResource($user),
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];

        // If the user is a brand type, also return their first brand
        if ($user->registration_type === 'brand') {
            $brand = Brand::where('user_id', $user->id)->first();
            if ($brand) {
                $data['brand'] = $this->brandResource($brand);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => $data,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/logout  (requires auth:api middleware)
    // -------------------------------------------------------------------------
    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if ($token) {
            // Revoke the specific session record
            JwtSession::where('token_hash', hash('sha256', $token))
                ->update([
                    'is_active'  => 0,
                    'revoked_at' => now(),
                ]);
        }

        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/auth/me  (requires auth:api middleware)
    // -------------------------------------------------------------------------
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        $data = ['user' => $this->userResource($user)];

        if ($user->registration_type === 'brand') {
            $brand = Brand::where('user_id', $user->id)->first();
            if ($brand) {
                $data['brand'] = $this->brandResource($brand);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/refresh  (requires auth:api middleware)
    // -------------------------------------------------------------------------
    public function refresh(Request $request): JsonResponse
    {
        try {
            $oldToken = $request->bearerToken();
            $newToken = auth('api')->refresh();

            // Revoke old session
            if ($oldToken) {
                JwtSession::where('token_hash', hash('sha256', $oldToken))
                    ->update(['is_active' => 0, 'revoked_at' => now()]);
            }

            // Store new session
            $user = auth('api')->user();
            $this->storeSession($user, $newToken, $request);

            return response()->json([
                'success'    => true,
                'token'      => $newToken,
                'token_type' => 'Bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed.',
            ], 401);
        }
    }

    // -------------------------------------------------------------------------
    // GET /api/auth/check-url?url=my-brand  (public)
    // -------------------------------------------------------------------------
    public function checkBrandUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/'],
        ], [
            'url.required' => 'URL slug is required.',
            'url.regex'    => 'URL may only contain lowercase letters, numbers, and hyphens.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success'   => false,
                'available' => false,
                'message'   => $validator->errors()->first('url'),
            ], 422);
        }

        $slug      = strtolower($request->url);
        $available = ! Brand::where('url', $slug)->exists();

        return response()->json([
            'success'   => true,
            'available' => $available,
            'url'       => 'card.vu/' . $slug,
            'message'   => $available ? 'URL is available.' : 'This URL is already taken.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Store the JWT token in jwt_sessions for multi-device tracking.
     */
    private function storeSession(User $user, string $token, Request $request): void
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
