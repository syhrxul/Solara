<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Resources\ClassScheduleResource;
use App\Models\ClassSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ClassScheduleController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = ClassSchedule::where('user_id', $request->user()->id)
            ->with('assignments')
            ->when($request->hari, fn ($q, $h) => $q->where('hari', $h))
            ->when($request->boolean('active_only'), fn ($q) => $q->where('is_active', true))
            ->when($request->semester, fn ($q, $s) => $q->where('semester', $s))
            ->orderByRaw("FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')")
            ->orderBy('waktu_mulai');

        return ClassScheduleResource::collection($query->paginate($request->per_page ?? 50))
            ->additional(['success' => true]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mata_kuliah'        => 'required|string|max:255',
            'kelas'              => 'nullable|string|max:50',
            'dosen'              => 'nullable|string|max:255',
            'media_pembelajaran' => 'nullable|string|max:255',
            'sks'                => 'nullable|integer|min:1',
            'sesi'               => 'nullable|integer',
            'hari'               => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'waktu_mulai'        => 'required|string',
            'waktu_selesai'      => 'nullable|string',
            'ruangan'            => 'nullable|string|max:255',
            'is_active'          => 'nullable|boolean',
            'semester'           => 'nullable|string|max:50',
        ]);

        $schedule = ClassSchedule::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return $this->success(new ClassScheduleResource($schedule), 'Jadwal berhasil dibuat', 201);
    }

    public function show(Request $request, ClassSchedule $classSchedule)
    {
        $this->authorizeUser($request, $classSchedule);

        return $this->success(new ClassScheduleResource($classSchedule->load('assignments')));
    }

    public function update(Request $request, ClassSchedule $classSchedule)
    {
        $this->authorizeUser($request, $classSchedule);

        $validated = $request->validate([
            'mata_kuliah'        => 'sometimes|string|max:255',
            'kelas'              => 'nullable|string|max:50',
            'dosen'              => 'nullable|string|max:255',
            'media_pembelajaran' => 'nullable|string|max:255',
            'sks'                => 'nullable|integer|min:1',
            'sesi'               => 'nullable|integer',
            'hari'               => 'sometimes|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'waktu_mulai'        => 'sometimes|string',
            'waktu_selesai'      => 'nullable|string',
            'ruangan'            => 'nullable|string|max:255',
            'is_active'          => 'nullable|boolean',
            'semester'           => 'nullable|string|max:50',
        ]);

        $classSchedule->update($validated);

        return $this->success(new ClassScheduleResource($classSchedule), 'Jadwal berhasil diperbarui');
    }

    public function destroy(Request $request, ClassSchedule $classSchedule)
    {
        $this->authorizeUser($request, $classSchedule);
        $classSchedule->delete();

        return $this->success(null, 'Jadwal berhasil dihapus');
    }

    /**
     * GET /api/schedules/today
     */
    public function today(Request $request)
    {
        $dayMap = [
            'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu',
        ];
        $todayId = $dayMap[now()->format('l')] ?? now()->format('l');

        $schedules = ClassSchedule::where('user_id', $request->user()->id)
            ->where('hari', $todayId)
            ->where('is_active', true)
            ->with('assignments')
            ->orderBy('waktu_mulai')
            ->get();

        return $this->success(ClassScheduleResource::collection($schedules), "Jadwal hari {$todayId}");
    }

    private function authorizeUser(Request $request, ClassSchedule $schedule)
    {
        if ($schedule->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
