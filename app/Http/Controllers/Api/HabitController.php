<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Resources\HabitResource;
use App\Http\Resources\HabitLogResource;
use App\Models\Habit;
use App\Models\HabitLog;
use Illuminate\Http\Request;

class HabitController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = $request->user()->habits()
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->when($request->frequency, fn ($q, $f) => $q->where('frequency', $f))
            ->latest();

        return HabitResource::collection($query->paginate($request->per_page ?? 15))
            ->additional(['success' => true]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'icon'           => 'nullable|string',
            'color'          => 'nullable|string',
            'frequency'      => 'required|in:daily,weekly',
            'frequency_days' => 'nullable|array',
            'target_count'   => 'nullable|integer|min:1',
            'unit'           => 'nullable|string|max:50',
            'reminder_time'  => 'nullable|string',
            'is_active'      => 'nullable|boolean',
            'started_at'     => 'nullable|date',
        ]);

        $habit = $request->user()->habits()->create($validated);

        return $this->success(new HabitResource($habit), 'Habit berhasil dibuat', 201);
    }

    public function show(Request $request, Habit $habit)
    {
        $this->authorizeUser($request, $habit);

        return $this->success(new HabitResource($habit));
    }

    public function update(Request $request, Habit $habit)
    {
        $this->authorizeUser($request, $habit);

        $validated = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'description'    => 'nullable|string',
            'icon'           => 'nullable|string',
            'color'          => 'nullable|string',
            'frequency'      => 'sometimes|in:daily,weekly',
            'frequency_days' => 'nullable|array',
            'target_count'   => 'nullable|integer|min:1',
            'unit'           => 'nullable|string|max:50',
            'reminder_time'  => 'nullable|string',
            'is_active'      => 'nullable|boolean',
        ]);

        $habit->update($validated);

        return $this->success(new HabitResource($habit), 'Habit berhasil diperbarui');
    }

    public function destroy(Request $request, Habit $habit)
    {
        $this->authorizeUser($request, $habit);
        $habit->delete();

        return $this->success(null, 'Habit berhasil dihapus');
    }

    /**
     * POST /api/habits/{habit}/log — Log/complete habit for today
     */
    public function log(Request $request, Habit $habit)
    {
        $this->authorizeUser($request, $habit);

        $validated = $request->validate([
            'count' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $log = HabitLog::updateOrCreate(
            [
                'habit_id'    => $habit->id,
                'user_id'     => $request->user()->id,
                'logged_date' => today(),
            ],
            [
                'count'     => $validated['count'] ?? 1,
                'completed' => true,
                'notes'     => $validated['notes'] ?? null,
            ]
        );

        // Update streak
        if (!$habit->isCompletedToday() || $log->wasRecentlyCreated) {
            $habit->increment('current_streak');
            $habit->update([
                'last_completed_date' => today(),
                'longest_streak'      => max($habit->longest_streak ?? 0, $habit->current_streak),
            ]);
        }

        return $this->success(new HabitLogResource($log), 'Habit berhasil dicatat');
    }

    /**
     * GET /api/habits/{habit}/logs — Get logs for a habit
     */
    public function logs(Request $request, Habit $habit)
    {
        $this->authorizeUser($request, $habit);

        $logs = $habit->logs()
            ->when($request->from, fn ($q, $d) => $q->where('logged_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->where('logged_date', '<=', $d))
            ->orderByDesc('logged_date')
            ->paginate($request->per_page ?? 30);

        return HabitLogResource::collection($logs)->additional(['success' => true]);
    }

    /**
     * POST /api/habits/{habit}/restore-streak
     */
    public function restoreStreak(Request $request, Habit $habit)
    {
        $this->authorizeUser($request, $habit);

        if ($habit->restoreStreak()) {
            return $this->success(new HabitResource($habit->fresh()), 'Streak berhasil di-restore');
        }

        return $this->error('Streak tidak bisa di-restore (sudah lebih dari 24 jam)', 422);
    }

    /**
     * GET /api/habits/stats — Habit statistics
     */
    public function stats(Request $request)
    {
        $habits = $request->user()->habits()->where('is_active', true)->get();

        $stats = [
            'total_habits'     => $habits->count(),
            'completed_today'  => $habits->filter(fn ($h) => $h->isCompletedToday())->count(),
            'pending_today'    => $habits->filter(fn ($h) => !$h->isCompletedToday() && $h->shouldDoOnDay())->count(),
            'longest_streak'   => $habits->max('longest_streak') ?? 0,
            'active_streaks'   => $habits->where('current_streak', '>', 0)->count(),
            'habits'           => $habits->map(fn ($h) => [
                'id'              => $h->id,
                'name'            => $h->name,
                'current_streak'  => $h->current_streak,
                'longest_streak'  => $h->longest_streak,
                'completed_today' => $h->isCompletedToday(),
                'completion_rate' => $h->completionRate(),
                'can_restore'     => $h->canRestoreStreak(),
            ]),
        ];

        return $this->success($stats);
    }

    private function authorizeUser(Request $request, Habit $habit)
    {
        if ($habit->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
