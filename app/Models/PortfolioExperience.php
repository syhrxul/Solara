<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioExperience extends Model
{
    protected $fillable = [
        'user_id', 'title', 'company', 'company_logo', 'location',
        'type', 'description', 'tech_stack', 'start_date', 'end_date',
        'is_current', 'is_visible', 'sort_order',
    ];

    protected $casts = [
        'tech_stack' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'is_visible' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
