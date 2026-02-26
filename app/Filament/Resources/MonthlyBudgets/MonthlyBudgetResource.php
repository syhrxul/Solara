<?php

namespace App\Filament\Resources\MonthlyBudgets;

use App\Filament\Resources\MonthlyBudgets\Pages\CreateMonthlyBudget;
use App\Filament\Resources\MonthlyBudgets\Pages\EditMonthlyBudget;
use App\Filament\Resources\MonthlyBudgets\Pages\ListMonthlyBudgets;
use App\Filament\Resources\MonthlyBudgets\Schemas\MonthlyBudgetForm;
use App\Filament\Resources\MonthlyBudgets\Tables\MonthlyBudgetsTable;
use App\Models\MonthlyBudget;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MonthlyBudgetResource extends Resource
{
    protected static ?string $model = MonthlyBudget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartPie;

    protected static ?string $navigationLabel = 'Budgeting Bulanan';

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan & Goals';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Budget';

    protected static ?string $pluralModelLabel = 'Budget Bulanan';

    public static function form(Schema $schema): Schema
    {
        return MonthlyBudgetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MonthlyBudgetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonthlyBudgets::route('/'),
            'create' => CreateMonthlyBudget::route('/create'),
            'edit' => EditMonthlyBudget::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canViewAny(): bool
    {
        $settings = auth()->user()->settings ?? [];
        return $settings['module_finance'] ?? true;
    }
}
