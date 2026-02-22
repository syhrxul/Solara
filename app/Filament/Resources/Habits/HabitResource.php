<?php

namespace App\Filament\Resources\Habits;

use App\Filament\Resources\Habits\Pages\CreateHabit;
use App\Filament\Resources\Habits\Pages\EditHabit;
use App\Filament\Resources\Habits\Pages\ListHabits;
use App\Filament\Resources\Habits\Schemas\HabitForm;
use App\Filament\Resources\Habits\Tables\HabitsTable;
use App\Models\Habit;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HabitResource extends Resource
{
    protected static ?string $model = Habit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFire;

    protected static ?string $navigationLabel = 'Habits';

    protected static string|UnitEnum|null $navigationGroup = 'Produktivitas';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Kebiasaan';

    protected static ?string $pluralModelLabel = 'Kebiasaan';

    public static function form(Schema $schema): Schema
    {
        return HabitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HabitsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListHabits::route('/'),
            'create' => CreateHabit::route('/create'),
            'edit'   => EditHabit::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canViewAny(): bool
    {
        $settings = auth()->user()->settings ?? [];
        return $settings['module_habits'] ?? true;
    }
}
