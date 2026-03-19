<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    protected $fillable = [
        'name',
        'thumbnail',
        'layout',
        'colors',
        'fonts',
        'is_active',
    ];

    /**
     * Cast JSON columns to arrays automatically.
     */
    protected $casts = [
        'colors' => 'array',
        'fonts'  => 'array',
    ];

    /**
     * Get all cards using this template.
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }
}
