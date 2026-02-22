<?php

namespace App\Filament\Widgets;

use App\Models\Habit;
use App\Models\HabitLog;
use Filament\Widgets\Widget;

class TodayHabitWidget extends Widget
{
    protected string $view = 'filament.widgets.today-habit-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 4;

    public $habitList = [];

    public static function canView(): bool
    {
        $settings = auth()->user()->settings ?? [];
        return $settings['module_habits'] ?? true;
    }

    public function mount()
    {
        $this->loadHabits();
    }

    public function loadHabits()
    {
        $habits = Habit::where('user_id', auth()->id())
            ->where('is_active', true)
            ->where('frequency', 'daily')
            ->get();

        $list = [];
        $today = now()->format('Y-m-d');

        foreach ($habits as $habit) {
            $log = HabitLog::where('habit_id', $habit->id)
                ->whereDate('logged_date', $today)
                ->first();

            $list[] = [
                'id' => $habit->id,
                'name' => $habit->name,
                'is_completed' => $log ? $log->completed : false,
                'color' => $habit->color ?? '#10b981',
            ];
        }

        $this->habitList = collect($list)->sortBy('is_completed')->values()->toArray();
    }

    public function toggleHabit($habitId)
    {
        $habit = Habit::where('id', $habitId)->where('user_id', auth()->id())->first();
        if (!$habit) return;

        $today = now()->format('Y-m-d');
        $log = HabitLog::firstOrNew([
            'habit_id' => $habit->id,
            'user_id' => auth()->id(),
            'logged_date' => $today,
        ]);

        $log->completed = !$log->completed;
        $log->count = $log->completed ? $habit->target_count : 0;
        $log->save();

        if ($log->completed) {
            $habit->current_streak += 1;
            if ($habit->current_streak > $habit->longest_streak) {
                $habit->longest_streak = $habit->current_streak;
            }
        } else {
            $habit->current_streak = max(0, $habit->current_streak - 1);
        }
        $habit->save();

        $this->loadHabits();
    }
}
