<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationSession extends Model
{
    protected $fillable = [
        'token',
        'registration_type',
        'full_name',
        'mobile_number',
        'country_code',
        'otp',
        'otp_expires_at',
        'otp_verified',
        'expires_at',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'expires_at'     => 'datetime',
        'otp_verified'   => 'boolean',
    ];

    /**
     * Check if the registration session has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the OTP has expired.
     */
    public function isOtpExpired(): bool
    {
        return $this->otp_expires_at && $this->otp_expires_at->isPast();
    }
}
