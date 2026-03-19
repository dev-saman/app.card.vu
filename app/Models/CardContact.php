<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardContact extends Model
{
    protected $fillable = [
        'card_id',

        // Contact Information
        'phone_number',
        'email_address',
        'whatsapp_number',
        'telegram',
        'website',
        'address',

        // Working Hours & Inquiry Emails (JSON)
        'working_hours',
        'inquiry_emails',

        // Social Media Links
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
     * Cast JSON columns to arrays automatically.
     */
    protected $casts = [
        'working_hours'  => 'array',
        'inquiry_emails' => 'array',
    ];

    /**
     * Get the card that owns these contacts.
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
