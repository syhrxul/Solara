<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use App\Models\GoalMilestone;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = $request->user()->goals()
            ->with(['category', 'milestones'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->boolean('is_pinned'), fn ($q) => $q->where('is_pinned', true))
            ->orderByDesc('is_pinned')
            ->latest();

        return GoalResource::collection($query->paginate($request->per_page ?? 15))
            ->additional(['success' => true]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'status'      => 'nullable|in:active,completed,paused,cancelled',
            'target_date' => 'nullable|date',
            'icon'        => 'nullable|string',
            'color'       => 'nullable|string',
            'is_pinned'   => 'nullable|boolean',
        ]);

        $goal = $request->user()->goals()->create($validated);

        return $this->success(new GoalResource($goal->load(['category', 'milestones'])), 'Goal berhasil dibuat', 201);
    }

    public function show(Request $request, Goal $goal)
    {
        $this->authorizeUser($request, $goal);

        return $this->success(new GoalResource($goal->load(['category', 'milestones'])));
    }

    public function update(Request $request, Goal $goal)
    {
        $this->authorizeUser($request, $goal);

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'status'      => 'nullable|in:active,completed,paused,cancelled',
            'target_date' => 'nullable|date',
            'icon'        => 'nullable|string',
            'color'       => 'nullable|string',
            'is_pinned'   => 'nullable|boolean',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'completed' && !$goal->completed_at) {
            $validated['completed_at'] = now();
            $validated['progress'] = 100;
        }

        $goal->update($validated);

        return $this->success(new GoalResource($goal->load(['category', 'milestones'])), 'Goal berhasil diperbarui');
    }

    public function destroy(Request $request, Goal $goal)
    {
        $this->authorizeUser($request, $goal);
        $goal->delete();

        return $this->success(null, 'Goal berhasil dihapus');
    }

    // --- Milestones ---

    public function storeMilestone(Request $request, Goal $goal)
    {
        $this->authorizeUser($request, $goal);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'target_date' => 'nullable|date',
            'sort_order'  => 'nullable|integer',
        ]);

        $milestone = $goal->milestones()->create($validated);
        $goal->recalculateProgress();

        return $this->success($milestone, 'Milestone berhasil ditambahkan', 201);
    }

    public function updateMilestone(Request $request, Goal $goal, GoalMilestone $milestone)
    {
        $this->authorizeUser($request, $goal);

        $validated = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'is_completed' => 'nullable|boolean',
            'target_date'  => 'nullable|date',
            'sort_order'   => 'nullable|integer',
        ]);

        if (isset($validated['is_completed']) && $validated['is_completed'] && !$milestone->completed_at) {
            $validated['completed_at'] = now();
        }

        $milestone->update($validated);
        $goal->recalculateProgress();

        return $this->success($milestone->fresh(), 'Milestone berhasil diperbarui');
    }

    public function destroyMilestone(Request $request, Goal $goal, GoalMilestone $milestone)
    {
        $this->authorizeUser($request, $goal);
        $milestone->delete();
        $goal->recalculateProgress();

        return $this->success(null, 'Milestone berhasil dihapus');
    }

    private function authorizeUser(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
