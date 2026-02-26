<?php

namespace App\Filament\Resources\FinanceTransactions\Pages;

use App\Filament\Resources\FinanceTransactions\FinanceTransactionResource;
use App\Filament\Resources\FinanceTransactions\Widgets\FinanceStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinanceTransactions extends ListRecords
{
    protected static string $resource = FinanceTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FinanceStatsWidget::class,
        ];
    }
}
