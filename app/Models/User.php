<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'registration_type',
        'status',
        'timezone',
        'country',
        'profile_picture',
        'mobile_number',
        'registration_step',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'status'            => 'boolean',
            'registration_type' => 'string',
        ];
    }

    // -------------------------------------------------------------------------
    // JWTSubject interface — required by tymon/jwt-auth
    // -------------------------------------------------------------------------

    /**
     * Get the identifier that will be stored in the JWT subject claim.
     */
    public function getJWTIdentifier(): mixed
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array of arbitrary claims to be added to the JWT payload.
     *
     * @return array<string, mixed>
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'name'              => $this->name,
            'email'             => $this->email,
            'mobile_number'     => $this->mobile_number,
            'registration_type' => $this->registration_type,
            'status'            => (bool) $this->status,
            'timezone'          => $this->timezone,
            'country'           => $this->country,
            'profile_picture'   => $this->profile_picture,
            'created_at'        => $this->created_at?->toISOString(),
        ];
    }
}
