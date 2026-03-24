<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginVerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_code'  => ['required', 'string', 'max:10'],
            'mobile_number' => ['required', 'string', 'max:20'],
            'otp'           => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'country_code.required'  => 'Country code is required.',
            'mobile_number.required' => 'Mobile number is required.',
            'otp.required'           => 'OTP is required.',
            'otp.size'               => 'OTP must be exactly 6 digits.',
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
