<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Resources\ClassAssignmentResource;
use App\Models\ClassAssignment;
use Illuminate\Http\Request;

class ClassAssignmentController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = ClassAssignment::where('user_id', $request->user()->id)
            ->with('classSchedule')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->class_schedule_id, fn ($q, $c) => $q->where('class_schedule_id', $c))
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->latest('deadline');

        return ClassAssignmentResource::collection($query->paginate($request->per_page ?? 15))
            ->additional(['success' => true]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_schedule_id' => 'required|exists:class_schedules,id',
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'type'              => 'nullable|string|max:50',
            'status'            => 'nullable|in:belum,dikerjakan,selesai',
            'deadline'          => 'nullable|date',
            'nilai'             => 'nullable|numeric',
        ]);

        $assignment = ClassAssignment::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return $this->success(
            new ClassAssignmentResource($assignment->load('classSchedule')),
            'Tugas kuliah berhasil dibuat', 201
        );
    }

    public function show(Request $request, ClassAssignment $classAssignment)
    {
        $this->authorizeUser($request, $classAssignment);

        return $this->success(new ClassAssignmentResource($classAssignment->load('classSchedule')));
    }

    public function update(Request $request, ClassAssignment $classAssignment)
    {
        $this->authorizeUser($request, $classAssignment);

        $validated = $request->validate([
            'class_schedule_id' => 'sometimes|exists:class_schedules,id',
            'title'             => 'sometimes|string|max:255',
            'description'       => 'nullable|string',
            'type'              => 'nullable|string|max:50',
            'status'            => 'nullable|in:belum,dikerjakan,selesai',
            'deadline'          => 'nullable|date',
            'nilai'             => 'nullable|numeric',
        ]);

        $classAssignment->update($validated);

        return $this->success(
            new ClassAssignmentResource($classAssignment->load('classSchedule')),
            'Tugas kuliah berhasil diperbarui'
        );
    }

    public function destroy(Request $request, ClassAssignment $classAssignment)
    {
        $this->authorizeUser($request, $classAssignment);
        $classAssignment->delete();

        return $this->success(null, 'Tugas kuliah berhasil dihapus');
    }

    private function authorizeUser(Request $request, ClassAssignment $assignment)
    {
        if ($assignment->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
