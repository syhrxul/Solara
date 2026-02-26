<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'bank_id',
        'title',
        'description',
        'amount',
        'type',
        'payment_method',
        'transaction_date',
        'is_recurring',
        'recurring_period',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'transaction_date' => 'date',
        'is_recurring'     => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'bank_id');
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('transaction_date', now()->year)
            ->whereMonth('transaction_date', now()->month);
    }
}
