<?php

namespace App\Filament\Resources\MonthlyBudgets\Pages;

use App\Filament\Resources\MonthlyBudgets\MonthlyBudgetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMonthlyBudgets extends ListRecords
{
    protected static string $resource = MonthlyBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
