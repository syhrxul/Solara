<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioProject extends Model
{
    protected $fillable = [
        'user_id', 'title', 'description', 'short_description', 'image',
        'category', 'demo_url', 'source_url', 'tags', 'screenshots',
        'is_featured', 'is_visible', 'sort_order', 'project_date',
    ];

    protected $casts = [
        'tags' => 'array',
        'screenshots' => 'array',
        'is_featured' => 'boolean',
        'is_visible' => 'boolean',
        'project_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
