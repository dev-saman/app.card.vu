<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Card extends Model
{
    protected $fillable = [
        'workspace_id',
        'template_id',
        'category_id',
        'brand_id',
        'location_id',
        'team_member_id',
        'name',
        'card_url',
        'headline',
        'specializations',
        'highlights',
        'google_business_profile',
        'theme',
        'profile_image',
        'bio',
        'qr_code',
        'status',
    ];

    /**
     * Cast JSON columns to arrays automatically.
     */
    protected $casts = [
        'specializations' => 'array',
        'highlights'      => 'array',
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
     * Get the contact details (contact info, social links, working hours) associated with the card.
     */
    public function contact(): HasOne
    {
        return $this->hasOne(CardContact::class);
    }
}
