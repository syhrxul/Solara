<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Http\Resources\FinanceTransactionResource;
use App\Models\FinanceTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceTransactionController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = $request->user()->financeTransactions()
            ->with(['category', 'bank'])
            ->when($request->type, fn ($q, $t) => $q->where('type', $t))
            ->when($request->category_id, fn ($q, $c) => $q->where('category_id', $c))
            ->when($request->bank_id, fn ($q, $b) => $q->where('bank_id', $b))
            ->when($request->from, fn ($q, $d) => $q->where('transaction_date', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->where('transaction_date', '<=', $d))
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($request->month, function ($q) use ($request) {
                $date = Carbon::parse($request->month . '-01');
                $q->whereYear('transaction_date', $date->year)
                  ->whereMonth('transaction_date', $date->month);
            })
            ->latest('transaction_date');

        return FinanceTransactionResource::collection($query->paginate($request->per_page ?? 15))
            ->additional(['success' => true]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'amount'           => 'required|numeric|min:0',
            'type'             => 'required|in:income,expense',
            'category_id'      => 'nullable|exists:categories,id',
            'bank_id'          => 'nullable|exists:categories,id',
            'payment_method'   => 'nullable|string',
            'transaction_date' => 'required|date',
            'is_recurring'     => 'nullable|boolean',
            'recurring_period' => 'nullable|string',
        ]);

        $transaction = $request->user()->financeTransactions()->create($validated);

        return $this->success(
            new FinanceTransactionResource($transaction->load(['category', 'bank'])),
            'Transaksi berhasil dicatat', 201
        );
    }

    public function show(Request $request, FinanceTransaction $financeTransaction)
    {
        $this->authorizeUser($request, $financeTransaction);

        return $this->success(new FinanceTransactionResource($financeTransaction->load(['category', 'bank'])));
    }

    public function update(Request $request, FinanceTransaction $financeTransaction)
    {
        $this->authorizeUser($request, $financeTransaction);

        $validated = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'description'      => 'nullable|string',
            'amount'           => 'sometimes|numeric|min:0',
            'type'             => 'sometimes|in:income,expense',
            'category_id'      => 'nullable|exists:categories,id',
            'bank_id'          => 'nullable|exists:categories,id',
            'payment_method'   => 'nullable|string',
            'transaction_date' => 'sometimes|date',
            'is_recurring'     => 'nullable|boolean',
            'recurring_period' => 'nullable|string',
        ]);

        $financeTransaction->update($validated);

        return $this->success(
            new FinanceTransactionResource($financeTransaction->load(['category', 'bank'])),
            'Transaksi berhasil diperbarui'
        );
    }

    public function destroy(Request $request, FinanceTransaction $financeTransaction)
    {
        $this->authorizeUser($request, $financeTransaction);
        $financeTransaction->delete();

        return $this->success(null, 'Transaksi berhasil dihapus');
    }

    /**
     * GET /api/finance/summary?month=2026-03
     */
    public function summary(Request $request)
    {
        $month = $request->month ?? now()->format('Y-m');
        $date = Carbon::parse($month . '-01');

        $transactions = $request->user()->financeTransactions()
            ->whereYear('transaction_date', $date->year)
            ->whereMonth('transaction_date', $date->month);

        $income = (clone $transactions)->where('type', 'income')->sum('amount');
        $expense = (clone $transactions)->where('type', 'expense')->sum('amount');

        // Per-category breakdown
        $byCategory = (clone $transactions)
            ->where('type', 'expense')
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(function ($items) {
                return [
                    'category' => $items->first()->category?->name ?? 'Tanpa Kategori',
                    'total'    => $items->sum('amount'),
                    'count'    => $items->count(),
                ];
            })->values();

        // Cumulative Balance up to the end of the selected month
        $totalIncome = $request->user()->financeTransactions()
            ->where('type', 'income')
            ->where('transaction_date', '<=', $date->endOfMonth())
            ->sum('amount');
            
        $totalExpense = $request->user()->financeTransactions()
            ->where('type', 'expense')
            ->where('transaction_date', '<=', $date->endOfMonth())
            ->sum('amount');

        return $this->success([
            'month'       => $month,
            'income'      => (float) $income,
            'expense'     => (float) $expense,
            'balance'     => (float) ($totalIncome - $totalExpense),
            'by_category' => $byCategory,
        ]);
    }

    private function authorizeUser(Request $request, FinanceTransaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized');
        }
    }
}
