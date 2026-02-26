<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyBudget extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'month_year',
        'amount',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getSpentAttribute(): float
    {
        $startDate = $this->month_year . '-01';
        $endDate = \Carbon\Carbon::parse($startDate)->endOfMonth()->format('Y-m-d');

        return \App\Models\FinanceTransaction::where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');
    }
}
