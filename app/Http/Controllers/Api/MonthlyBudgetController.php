<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Resources\MonthlyBudgetResource;
use App\Models\MonthlyBudget;
use Illuminate\Http\Request;

class MonthlyBudgetController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = MonthlyBudget::where('user_id', $request->user()->id)
            ->with('category')
            ->when($request->month_year, fn ($q, $m) => $q->where('month_year', $m))
            ->latest('month_year');

        return MonthlyBudgetResource::collection($query->paginate($request->per_page ?? 15))
            ->additional(['success' => true]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'month_year'  => 'required|string', // format: 2026-03
            'amount'      => 'required|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

        $budget = MonthlyBudget::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        return $this->success(new MonthlyBudgetResource($budget->load('category')), 'Budget berhasil dibuat', 201);
    }

    public function show(Request $request, MonthlyBudget $monthlyBudget)
    {
        $this->authorizeUser($request, $monthlyBudget);

        return $this->success(new MonthlyBudgetResource($monthlyBudget->load('category')));
    }

    public function update(Request $request, MonthlyBudget $monthlyBudget)
    {
        $this->authorizeUser($request, $monthlyBudget);

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'month_year'  => 'sometimes|string',
            'amount'      => 'sometimes|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

        $monthlyBudget->update($validated);

        return $this->success(new MonthlyBudgetResource($monthlyBudget->load('category')), 'Budget berhasil diperbarui');
    }

    public function destroy(Request $request, MonthlyBudget $monthlyBudget)
    {
        $this->authorizeUser($request, $monthlyBudget);
        $monthlyBudget->delete();

        return $this->success(null, 'Budget berhasil dihapus');
    }

    private function authorizeUser(Request $request, MonthlyBudget $budget)
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
