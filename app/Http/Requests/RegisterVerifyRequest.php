<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterVerifyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registration_token' => ['required', 'string', 'exists:registration_sessions,token'],
            'otp'                => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'registration_token.required' => 'Registration token is required.',
            'registration_token.exists'   => 'Invalid or expired registration session.',
            'otp.required'                => 'OTP is required.',
            'otp.size'                    => 'OTP must be exactly 6 digits.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status'  => false,
            'message' => $validator->errors()->first(),
            'errors'  => $validator->errors(),
        ], 422));
    }
}
