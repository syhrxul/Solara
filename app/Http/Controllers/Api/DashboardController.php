<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Task;
use App\Models\Habit;
use App\Models\FinanceTransaction;
use App\Models\ClassSchedule;
use App\Models\ClassAssignment;
use App\Models\Goal;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        // Tasks stats
        $pendingTasks = $user->tasks()->where('status', 'pending')->count();
        $overdueTasks = $user->tasks()
            ->where('status', 'pending')
            ->where('due_date', '<', $today)
            ->count();
        $todayTasks = $user->tasks()->where('due_date', $today)->count();
        $completedTasks = $user->tasks()->where('status', 'completed')
            ->whereDate('completed_at', $today)->count();

        // Habits stats
        $activeHabits = $user->habits()->where('is_active', true)->get();
        $completedHabits = $activeHabits->filter(fn ($h) => $h->isCompletedToday())->count();
        $pendingHabits = $activeHabits->filter(fn ($h) => !$h->isCompletedToday() && $h->shouldDoOnDay())->count();

        // Finance this month
        $monthIncome = $user->financeTransactions()
            ->where('type', 'income')->thisMonth()->sum('amount');
        $monthExpense = $user->financeTransactions()
            ->where('type', 'expense')->thisMonth()->sum('amount');

        // Schedule today
        $dayMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
        ];
        $todayDay = $dayMap[now()->format('l')] ?? now()->format('l');
        $todaySchedules = ClassSchedule::where('user_id', $user->id)
            ->where('hari', $todayDay)->where('is_active', true)->count();

        // Assignments
        $pendingAssignments = ClassAssignment::where('user_id', $user->id)
            ->where('status', '!=', 'selesai')->count();
        $overdueAssignments = ClassAssignment::where('user_id', $user->id)
            ->where('status', '!=', 'selesai')
            ->where('deadline', '<', $today)->count();

        // Goals
        $activeGoals = $user->goals()->where('status', 'active')->count();

        return $this->success([
            'tasks' => [
                'pending'        => $pendingTasks,
                'overdue'        => $overdueTasks,
                'today'          => $todayTasks,
                'completed_today' => $completedTasks,
            ],
            'habits' => [
                'active'          => $activeHabits->count(),
                'completed_today' => $completedHabits,
                'pending_today'   => $pendingHabits,
            ],
            'finance' => [
                'month_income'  => (float) $monthIncome,
                'month_expense' => (float) $monthExpense,
                'month_balance' => (float) ($monthIncome - $monthExpense),
            ],
            'schedule' => [
                'today_classes'       => $todaySchedules,
                'today_day'           => $todayDay,
                'pending_assignments' => $pendingAssignments,
                'overdue_assignments' => $overdueAssignments,
            ],
            'goals' => [
                'active' => $activeGoals,
            ],
        ]);
    }
}
