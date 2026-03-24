<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registration_token' => ['required', 'string', 'exists:registration_sessions,token'],
            'full_name'          => ['required', 'string', 'max:255'],
            'country_code'       => ['required', 'string', 'max:10'],
            'mobile_number'      => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'registration_token.required' => 'Registration token is required.',
            'registration_token.exists'   => 'Invalid or expired registration session.',
            'full_name.required'          => 'Full name is required.',
            'country_code.required'       => 'Country code is required.',
            'mobile_number.required'      => 'Mobile number is required.',
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
