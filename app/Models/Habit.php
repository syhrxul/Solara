<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'icon',
        'color',
        'frequency',
        'frequency_days',
        'target_count',
        'unit',
        'reminder_time',
        'is_active',
        'current_streak',
        'longest_streak',
        'started_at',
    ];

    protected $casts = [
        'frequency_days' => 'array',
        'is_active'      => 'boolean',
        'started_at'     => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(HabitLog::class);
    }

    public function todayLog()
    {
        return $this->logs()->where('logged_date', today())->first();
    }

    public function isCompletedToday(): bool
    {
        $log = $this->todayLog();
        return $log && $log->completed;
    }

    public function completionRate(int $days = 30): float
    {
        $start = Carbon::today()->subDays($days);
        $total = $this->logs()->where('logged_date', '>=', $start)->count();
        if ($total === 0) {
            return 0;
        }
        $completed = $this->logs()
            ->where('logged_date', '>=', $start)
            ->where('completed', true)
            ->count();

        return round(($completed / $days) * 100, 1);
    }
}
