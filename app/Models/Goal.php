<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'status',
        'progress',
        'target_date',
        'icon',
        'color',
        'is_pinned',
        'completed_at',
    ];

    protected $casts = [
        'target_date'  => 'date',
        'is_pinned'    => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(GoalMilestone::class)->orderBy('sort_order');
    }

    public function recalculateProgress(): void
    {
        $total = $this->milestones()->count();
        if ($total === 0) {
            return;
        }
        $completed = $this->milestones()->where('is_completed', true)->count();
        $this->update(['progress' => (int) round(($completed / $total) * 100)]);
    }
}
