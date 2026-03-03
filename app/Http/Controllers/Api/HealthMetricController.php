<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Resources\HealthMetricResource;
use App\Models\HealthMetric;
use Illuminate\Http\Request;

class HealthMetricController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = HealthMetric::where('user_id', $request->user()->id)
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->from, fn ($q, $d) => $q->where('date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->where('date', '<=', $d))
            ->latest('date');

        return HealthMetricResource::collection($query->paginate($request->per_page ?? 30))
            ->additional(['success' => true]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'    => 'required|date',
            'type'    => 'required|string|max:50',
            'value'   => 'required|numeric',
            'details' => 'nullable|array',
        ]);

        $metric = HealthMetric::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return $this->success(new HealthMetricResource($metric), 'Data kesehatan berhasil dicatat', 201);
    }

    public function show(Request $request, HealthMetric $healthMetric)
    {
        $this->authorizeUser($request, $healthMetric);

        return $this->success(new HealthMetricResource($healthMetric));
    }

    public function update(Request $request, HealthMetric $healthMetric)
    {
        $this->authorizeUser($request, $healthMetric);

        $validated = $request->validate([
            'date'    => 'sometimes|date',
            'type'    => 'sometimes|string|max:50',
            'value'   => 'sometimes|numeric',
            'details' => 'nullable|array',
        ]);

        $healthMetric->update($validated);

        return $this->success(new HealthMetricResource($healthMetric), 'Data kesehatan berhasil diperbarui');
    }

    public function destroy(Request $request, HealthMetric $healthMetric)
    {
        $this->authorizeUser($request, $healthMetric);
        $healthMetric->delete();

        return $this->success(null, 'Data kesehatan berhasil dihapus');
    }

    private function authorizeUser(Request $request, HealthMetric $metric)
    {
        if ($metric->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
