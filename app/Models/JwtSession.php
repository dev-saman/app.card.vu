<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JwtSession extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'token_hash',
        'device_name',
        'ip_address',
        'user_agent',
        'expires_at',
        'last_used_at',
        'revoked_at',
        'is_active',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'expires_at'   => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at'   => 'datetime',
    ];

    protected $hidden = ['token'];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Mark this session as revoked (logout). */
    public function revoke(): void
    {
        $this->update([
            'is_active'  => 0,
            'revoked_at' => now(),
        ]);
    }

    /** Touch last_used_at timestamp (call on every authenticated request). */
    public function touchLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /** Scope: only active (non-revoked, non-expired) sessions. */
    public function scopeActive($query)
    {
        return $query->where('is_active', 1)
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }
}
