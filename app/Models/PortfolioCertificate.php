<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioCertificate extends Model
{
    protected $fillable = [
        'user_id', 'title', 'issuer', 'credential_id', 'credential_url',
        'image', 'issued_date', 'expiry_date', 'tags', 'is_visible', 'sort_order',
    ];

    protected $casts = [
        'tags' => 'array',
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'is_visible' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
