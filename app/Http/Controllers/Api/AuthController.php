<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\JwtSession;
use App\Models\User;
use App\Models\Workspace;
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
                'status' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 1. Create user account
            $user = User::create([
                'name'              => $request->name,
                'email'             => $request->email,
                'mobile_number'     => $request->mobile_number,
                'password'          => $request->password,
                'registration_type' => 'professional',
                'status'            => 1,
                'timezone'          => $request->timezone,
                'country'           => $request->country,
                'profile_picture'   => $request->profile_picture,
            ]);

            // 2. Issue JWT and store session
            $token = auth('api')->login($user);
            $this->storeSession($user, $token, $request);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Professional account created successfully.',
                'data'    => [
                    'token'      => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Registration failed. Please try again.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/register/brand
    //
    // Step 1 — Account:   name, email, mobile_number, password
    // Step 2 — Workspace: workspace_name, owner_name, country, currency, timezone
    // Step 3 — Brand:     brand_name, brand_url, brand_category
    // -------------------------------------------------------------------------
    public function registerBrand(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // --- Step 1: User account ---
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile_number'    => ['required', 'string', 'max:20'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'profile_picture'  => ['nullable', 'url', 'max:500'],

            // --- Step 2: Workspace ---
            'workspace_name'   => ['required', 'string', 'max:255'],
            'owner_name'       => ['required', 'string', 'max:255'],
            'country'          => ['required', 'string', 'max:100'],
            'currency'         => ['required', 'string', 'max:10'],
            'timezone'         => ['required', 'string', 'max:100'],

            // --- Step 3: Brand ---
            'brand_name'       => ['required', 'string', 'max:255'],
            'brand_url'        => ['required', 'string', 'max:255', 'unique:brands,brand_url', 'regex:/^[a-z0-9\-]+$/'],
            'brand_category'   => ['nullable', 'string', 'max:100'],
        ], [
            // Account
            'name.required'            => 'Full name is required.',
            'email.required'           => 'Email address is required.',
            'email.email'              => 'Please enter a valid email address.',
            'email.unique'             => 'This email is already registered.',
            'mobile_number.required'   => 'Mobile number is required.',
            'password.required'        => 'Password is required.',
            'password.min'             => 'Password must be at least 8 characters.',
            'password.confirmed'       => 'Passwords do not match.',
            // Workspace
            'workspace_name.required'  => 'Workspace name is required.',
            'owner_name.required'      => 'Admin / Owner name is required.',
            'country.required'         => 'Country is required.',
            'currency.required'        => 'Currency is required.',
            'timezone.required'        => 'Timezone is required.',
            // Brand
            'brand_name.required'      => 'Brand name is required.',
            'brand_url.required'       => 'Brand URL is required.',
            'brand_url.unique'         => 'This brand URL is already taken.',
            'brand_url.regex'          => 'Brand URL may only contain lowercase letters, numbers, and hyphens.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            // 1. Create user account
            $user = User::create([
                'name'              => $request->name,
                'email'             => $request->email,
                'mobile_number'     => $request->mobile_number,
                'password'          => $request->password,
                'registration_type' => 'brand',
                'status'            => 1,
                'timezone'          => $request->timezone,
                'country'           => $request->country,
                'profile_picture'   => $request->profile_picture,
            ]);

            // 2. Create workspace linked to the user
            $workspace = Workspace::create([
                'user_id'    => $user->id,
                'name'       => $request->workspace_name,
                'owner_name' => $request->owner_name,
                'country'    => $request->country,
                'currency'   => $request->currency,
                'timezone'   => $request->timezone,
                'status'     => 'active',
            ]);

            // 3. Resolve category — find by name or create if not found
            $category = null;
            if ($request->brand_category) {
                $category = Category::firstOrCreate(
                    ['name' => $request->brand_category],
                    ['status' => 'active']
                );
            }

            // 4. Create brand linked to the workspace (and optional category)
            $brand = Brand::create([
                'workspace_id' => $workspace->id,
                'category_id'  => $category?->id,
                'name'         => $request->brand_name,
                'brand_url'    => strtolower($request->brand_url),
                'status'       => 'active',
            ]);

            DB::commit();

            $token = auth('api')->login($user);
            $this->storeSession($user, $token, $request);

            return response()->json([
                'status' => true,
                'message' => 'Brand account created successfully.',
                'data'    => [
                    'token'      => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
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
                'status' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('email', 'password');

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        /** @var User $user */
        $user = auth('api')->user();

        if (! $user->status) {
            auth('api')->logout();
            return response()->json([
                'status' => false,
                'message' => 'Your account is inactive. Please contact support.',
            ], 403);
        }

        $this->storeSession($user, $token, $request);

        return response()->json([
            'status' => true,
            'message' => 'Login successful.',
            'data'    => [
                'token'      => $token,
                'token_type' => 'Bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/logout  (requires auth:api middleware)
    // -------------------------------------------------------------------------
    public function logout(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if ($token) {
            JwtSession::where('token_hash', hash('sha256', $token))
                ->update([
                    'is_active'  => 0,
                    'revoked_at' => now(),
                ]);
        }

        auth('api')->logout();

        return response()->json([
            'status' => true,
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
            $workspace = Workspace::where('user_id', $user->id)->first();
            if ($workspace) {
                $data['workspace'] = $this->workspaceResource($workspace);
                $brand = Brand::where('workspace_id', $workspace->id)->first();
                if ($brand) {
                    $data['brand'] = $this->brandResource($brand);
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'User retrieved successfully.',
            'data'    => $data,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /api/auth/refresh  (requires auth:api middleware)
    // -------------------------------------------------------------------------
    public function refresh(Request $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $oldToken = $request->bearerToken();

            // 1. Rotate the JWT
            $newToken = auth('api')->refresh();

            // 2. Revoke the old session record
            if ($oldToken) {
                JwtSession::where('token_hash', hash('sha256', $oldToken))
                    ->update(['is_active' => 0, 'revoked_at' => now()]);
            }

            // 3. Store the new session record
            $user = auth('api')->user();
            $this->storeSession($user, $newToken, $request);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Token refreshed successfully.',
                'data'    => [
                    'token'      => $newToken,
                    'token_type' => 'Bearer',
                    'expires_in' => auth('api')->factory()->getTTL() * 60,
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Token refresh failed.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
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
                'status'   => false,
                'available' => false,
                'message'   => $validator->errors()->first('url'),
            ], 422);
        }

        $slug      = strtolower($request->url);
        $available = ! Brand::where('brand_url', $slug)->exists();

        return response()->json([
            'status'   => true,
            'available' => $available,
            'url'       => 'card.vu/' . $slug,
            'message'   => $available ? 'URL is available.' : 'This URL is already taken.',
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

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

    private function workspaceResource(Workspace $workspace): array
    {
        return [
            'id'         => $workspace->id,
            'name'       => $workspace->name,
            'owner_name' => $workspace->owner_name,
            'country'    => $workspace->country,
            'currency'   => $workspace->currency,
            'timezone'   => $workspace->timezone,
            'status'     => $workspace->status,
            'created_at' => $workspace->created_at,
        ];
    }

    private function brandResource(Brand $brand): array
    {
        return [
            'id'           => $brand->id,
            'workspace_id' => $brand->workspace_id,
            'name'         => $brand->name,
            'url'          => 'card.vu/' . $brand->brand_url,
            'slug'         => $brand->brand_url,
            'category'     => $brand->category?->name,
            'status'       => $brand->status,
            'created_at'   => $brand->created_at,
        ];
    }
}
