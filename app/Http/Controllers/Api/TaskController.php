<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = $request->user()->tasks()
            ->with('category')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn ($q, $p) => $q->where('priority', $p))
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($request->today, fn ($q) => $q->where('due_date', today()))
            ->when($request->boolean('is_pinned'), fn ($q) => $q->where('is_pinned', true))
            ->orderByDesc('is_pinned')
            ->orderBy('sort_order')
            ->latest();

        return TaskResource::collection($query->paginate($request->per_page ?? 15))
            ->additional(['success' => true]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'priority'    => 'nullable|in:low,medium,high,urgent',
            'status'      => 'nullable|in:pending,in_progress,completed',
            'due_date'    => 'nullable|date',
            'due_time'    => 'nullable|string',
            'is_pinned'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer',
        ]);

        $task = $request->user()->tasks()->create($validated);

        return $this->success(new TaskResource($task->load('category')), 'Task berhasil dibuat', 201);
    }

    public function show(Request $request, Task $task)
    {
        $this->authorizeUser($request, $task);

        return $this->success(new TaskResource($task->load('category')));
    }

    public function update(Request $request, Task $task)
    {
        $this->authorizeUser($request, $task);

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'priority'    => 'nullable|in:low,medium,high,urgent',
            'status'      => 'nullable|in:pending,in_progress,completed',
            'due_date'    => 'nullable|date',
            'due_time'    => 'nullable|string',
            'is_pinned'   => 'nullable|boolean',
            'sort_order'  => 'nullable|integer',
        ]);

        // Auto-set completed_at
        if (isset($validated['status']) && $validated['status'] === 'completed' && !$task->completed_at) {
            $validated['completed_at'] = now();
        }

        $task->update($validated);

        return $this->success(new TaskResource($task->load('category')), 'Task berhasil diperbarui');
    }

    public function destroy(Request $request, Task $task)
    {
        $this->authorizeUser($request, $task);
        $task->delete();

        return $this->success(null, 'Task berhasil dihapus');
    }

    private function authorizeUser(Request $request, Task $task)
    {
        if ($task->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
