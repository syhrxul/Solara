<?php


namespace App\Filament\Resources\FinanceTransactions;

use App\Filament\Resources\FinanceTransactions\Pages\CreateFinanceTransaction;
use App\Filament\Resources\FinanceTransactions\Pages\EditFinanceTransaction;
use App\Filament\Resources\FinanceTransactions\Pages\ListFinanceTransactions;
use App\Filament\Resources\FinanceTransactions\Schemas\FinanceTransactionForm;
use App\Filament\Resources\FinanceTransactions\Tables\FinanceTransactionsTable;
use App\Models\FinanceTransaction;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FinanceTransactionResource extends Resource
{
    protected static ?string $model = FinanceTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Keuangan';

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan & Goals';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Transaksi';

    protected static ?string $pluralModelLabel = 'Transaksi';

    public static function form(Schema $schema): Schema
    {
        return FinanceTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinanceTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListFinanceTransactions::route('/'),
            'create' => CreateFinanceTransaction::route('/create'),
            'edit'   => EditFinanceTransaction::route('/{record}/edit'),
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
