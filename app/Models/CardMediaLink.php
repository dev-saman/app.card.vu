<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardMediaLink extends Model
{
    protected $fillable = [
        'card_id',
        'linkedin',
        'instagram',
        'youtube',
        'facebook',
        'x_twitter',
        'github',
        'behance',
        'dribbble',
    ];

    /**
     * Get the card that owns the media links.
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
