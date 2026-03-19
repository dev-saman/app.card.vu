<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Card extends Model
{
    protected $fillable = [
        'workspace_id',
        'brand_id',
        'location_id',
        'team_member_id',
        'name',
        'theme',
        'profile_image',
        'bio',
        'qr_code',
        'status',
    ];

    /**
     * Get the workspace that owns the card.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the brand associated with the card.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the details (contact info, social links, working hours) associated with the card.
     */
    public function details(): HasOne
    {
        return $this->hasOne(CardDetail::class);
    }
}
