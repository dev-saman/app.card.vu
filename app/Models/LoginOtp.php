<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginOtp extends Model
{
    protected $fillable = [
        'mobile_number',
        'otp',
        'expires_at',
        'is_used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used'    => 'boolean',
    ];

    /**
     * Check if the OTP has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
