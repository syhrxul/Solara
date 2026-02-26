<?php

namespace App\Filament\Resources\FinanceTransactions\Widgets;

use App\Models\FinanceTransaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class FinanceStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $income = FinanceTransaction::where('user_id', auth()->id())
            ->where('type', 'income')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $expense = FinanceTransaction::where('user_id', auth()->id())
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');

        $balance = $income - $expense;

        return [
            Stat::make('Pemasukan Bulan Ini', 'Rp ' . number_format($income, 0, ',', '.'))
                ->description('Total pemasukan di bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Pengeluaran Bulan Ini', 'Rp ' . number_format($expense, 0, ',', '.'))
                ->description('Total pengeluaran di bulan ini')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            Stat::make('Sisa Saldo', 'Rp ' . number_format($balance, 0, ',', '.'))
                ->description('Pemasukan dikurangi pengeluaran')
                ->descriptionIcon('heroicon-m-building-library')
                ->color($balance >= 0 ? 'success' : 'danger'),
        ];
    }
}
