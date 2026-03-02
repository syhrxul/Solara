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
        'last_completed_date',
        'streak_broken_at',
        'streak_before_break',
        'started_at',
    ];

    protected $casts = [
        'frequency_days'      => 'array',
        'is_active'           => 'boolean',
        'started_at'          => 'date',
        'last_completed_date' => 'date',
        'streak_broken_at'    => 'datetime',
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

    /**
     * Check apakah streak bisa di-restore.
     * Bisa restore jika streak putus kurang dari 24 jam yang lalu.
     */
    public function canRestoreStreak(): bool
    {
        if (!$this->streak_broken_at) {
            return false;
        }

        return $this->streak_broken_at->diffInHours(now()) < 24
            && $this->streak_before_break > 0;
    }

    /**
     * Check apakah habit ini seharusnya dilakukan pada hari tertentu.
     */
    public function shouldDoOnDay(?string $dayOfWeek = null): bool
    {
        $dayOfWeek = $dayOfWeek ?: strtolower(now()->englishDayOfWeek);

        if ($this->frequency === 'daily') {
            return true;
        }

        if ($this->frequency === 'weekly') {
            $days = is_string($this->frequency_days)
                ? json_decode($this->frequency_days, true)
                : $this->frequency_days;

            return is_array($days) && in_array($dayOfWeek, $days);
        }

        return false;
    }

    /**
     * Restore streak yang sudah putus (dalam 24 jam).
     * Mengembalikan streak lama tapi entry saat ini TIDAK dihitung sebagai
     * tambahan streak — akan dihitung pada completion berikutnya.
     */
    public function restoreStreak(): bool
    {
        if (!$this->canRestoreStreak()) {
            return false;
        }

        $this->update([
            'current_streak'     => $this->streak_before_break,
            'streak_broken_at'   => null,
            'streak_before_break' => 0,
            // last_completed_date di-set ke kemarin (karena hari ini adalah restore,
            // bukan completion baru — streak baru dihitung pada completion berikutnya)
            'last_completed_date' => today()->subDay(),
        ]);

        return true;
    }

    /**
     * Putuskan streak habit ini.
     */
    public function breakStreak(): void
    {
        if ($this->current_streak <= 0) {
            return;
        }

        $this->update([
            'streak_before_break' => $this->current_streak,
            'streak_broken_at'    => now(),
            'current_streak'      => 0,
        ]);
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
