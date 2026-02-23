<?php

namespace App\Filament\Widgets;

use App\Models\FinanceTransaction;
use App\Models\Habit;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    // Auto-refresh every 60 seconds for clock
    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $userId = auth()->id();
        $settings = auth()->user()->settings ?? [];
        $today = today();
        $now = now();

        // Hari & Tanggal & Jam
        $days = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
        ];
        $hari = $days[$now->format('l')] ?? $now->format('l');

        $stats = [];

        // Clock & Date stat (always shown)
        $stats[] = Stat::make("📅 {$hari}", $now->format('H:i'))
            ->description($now->format('d F Y'))
            ->descriptionIcon('heroicon-m-clock')
            ->color('primary');

        // Tasks stats
        if ($settings['module_tasks'] ?? true) {
            $pendingTasks = Task::where('user_id', $userId)
                ->where('status', 'pending')
                ->count();
            $todayTasks = Task::where('user_id', $userId)
                ->where('due_date', $today)
                ->count();

            $stats[] = Stat::make('📋 Tugas Pending', $pendingTasks)
                ->description($todayTasks . ' tugas hari ini')
                ->descriptionIcon('heroicon-m-calendar')
                ->color($pendingTasks > 5 ? 'warning' : 'success');
        }

        // Habits stats
        if ($settings['module_habits'] ?? true) {
            $activeHabits = Habit::where('user_id', $userId)
                ->where('is_active', true)
                ->count();
            $completedHabitsToday = Habit::where('user_id', $userId)
                ->where('is_active', true)
                ->whereHas('logs', function ($q) use ($today) {
                    $q->where('logged_date', $today)->where('completed', true);
                })
                ->count();

            $stats[] = Stat::make('🔥 Habit Selesai Hari Ini', $completedHabitsToday . '/' . $activeHabits)
                ->description('Dari ' . $activeHabits . ' habit aktif')
                ->descriptionIcon('heroicon-m-fire')
                ->color($completedHabitsToday >= $activeHabits && $activeHabits > 0 ? 'success' : 'info');
        }

        // Finance stats bulan ini
        if ($settings['module_finance'] ?? true) {
            $monthIncome = FinanceTransaction::where('user_id', $userId)
                ->income()
                ->thisMonth()
                ->sum('amount');
            $monthExpense = FinanceTransaction::where('user_id', $userId)
                ->expense()
                ->thisMonth()
                ->sum('amount');

            $stats[] = Stat::make('💰 Saldo Bulan Ini', 'Rp ' . number_format($monthIncome - $monthExpense, 0, ',', '.'))
                ->description('Masuk: Rp ' . number_format($monthIncome, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color(($monthIncome - $monthExpense) >= 0 ? 'success' : 'danger');
        }

        return $stats;
    }
}
