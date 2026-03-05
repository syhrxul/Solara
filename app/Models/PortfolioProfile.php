<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioProfile extends Model
{
    protected $fillable = [
        'user_id', 'name', 'tagline', 'bio', 'location', 'avatar',
        'resume_url', 'email', 'github_url', 'linkedin_url',
        'website_url', 'twitter_url', 'instagram_url',
        'skills', 'is_open_to_work',
    ];

    protected $casts = [
        'skills' => 'array',
        'is_open_to_work' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
