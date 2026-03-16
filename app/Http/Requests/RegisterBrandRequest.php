<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // --- User account fields ---
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255', 'unique:users,email'],
            'mobile_number'   => ['required', 'string', 'max:20'],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
            'timezone'        => ['nullable', 'string', 'max:100'],
            'country'         => ['nullable', 'string', 'max:100'],
            'profile_picture' => ['nullable', 'url', 'max:500'],

            // --- Brand fields ---
            'brand_name'      => ['required', 'string', 'max:255'],
            'brand_url'       => ['required', 'string', 'max:255', 'unique:brands,url', 'regex:/^[a-z0-9\-]+$/'],
            'brand_category'  => ['required', 'string', 'max:100'],
            'brand_owner_name'=> ['nullable', 'string', 'max:255'],
            'brand_country'   => ['nullable', 'string', 'max:100'],
            'brand_timezone'  => ['nullable', 'string', 'max:100'],
            'brand_currency'  => ['nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
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
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }
}
