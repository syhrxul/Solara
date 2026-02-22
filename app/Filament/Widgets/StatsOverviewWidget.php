<?php

namespace App\Filament\Widgets;

use App\Models\FinanceTransaction;
use App\Models\Goal;
use App\Models\Habit;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $userId = auth()->id();
        $today = today();

        // Tasks stats
        $pendingTasks = Task::where('user_id', $userId)
            ->where('status', 'pending')
            ->count();
        $todayTasks = Task::where('user_id', $userId)
            ->where('due_date', $today)
            ->count();

        // Habits stats
        $activeHabits = Habit::where('user_id', $userId)
            ->where('is_active', true)
            ->count();
        $completedHabitsToday = Habit::where('user_id', $userId)
            ->where('is_active', true)
            ->whereHas('logs', function ($q) use ($today) {
                $q->where('logged_date', $today)->where('completed', true);
            })
            ->count();

        // Finance stats bulan ini
        $monthIncome = FinanceTransaction::where('user_id', $userId)
            ->income()
            ->thisMonth()
            ->sum('amount');
        $monthExpense = FinanceTransaction::where('user_id', $userId)
            ->expense()
            ->thisMonth()
            ->sum('amount');

        // Goals
        $activeGoals = Goal::where('user_id', $userId)
            ->where('status', 'in_progress')
            ->count();

        return [
            Stat::make('📋 Tugas Pending', $pendingTasks)
                ->description($todayTasks . ' tugas hari ini')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($pendingTasks > 5 ? 'warning' : 'success'),

            Stat::make('🔥 Habit Selesai Hari Ini', $completedHabitsToday . '/' . $activeHabits)
                ->description('Dari ' . $activeHabits . ' habit aktif')
                ->descriptionIcon('heroicon-m-fire')
                ->color($completedHabitsToday >= $activeHabits && $activeHabits > 0 ? 'success' : 'info'),

            Stat::make('💰 Saldo Bulan Ini', 'Rp ' . number_format($monthIncome - $monthExpense, 0, ',', '.'))
                ->description('Masuk: Rp ' . number_format($monthIncome, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color(($monthIncome - $monthExpense) >= 0 ? 'success' : 'danger'),

            Stat::make('🎯 Goals Aktif', $activeGoals)
                ->description('Sedang dikerjakan')
                ->descriptionIcon('heroicon-m-flag')
                ->color('info'),
        ];
    }
}
