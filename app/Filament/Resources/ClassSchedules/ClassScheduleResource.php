<?php

namespace App\Filament\Resources\ClassSchedules;

use App\Filament\Resources\ClassSchedules\Pages\CreateClassSchedule;
use App\Filament\Resources\ClassSchedules\Pages\EditClassSchedule;
use App\Filament\Resources\ClassSchedules\Pages\ListClassSchedules;
use App\Filament\Resources\ClassSchedules\Schemas\ClassScheduleForm;
use App\Filament\Resources\ClassSchedules\Tables\ClassSchedulesTable;
use App\Models\ClassSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ClassScheduleResource extends Resource
{
    protected static ?string $model = ClassSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Jadwal Kuliah';

    protected static string|UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Jadwal';

    protected static ?string $pluralModelLabel = 'Jadwal Kuliah';

    public static function getNavigationBadge(): ?string
    {
        $count = ClassSchedule::today()
            ->where('user_id', auth()->id())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
    }

    public static function form(Schema $schema): Schema
    {
        return ClassScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClassSchedulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListClassSchedules::route('/'),
            'create' => CreateClassSchedule::route('/create'),
            'edit'   => EditClassSchedule::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canViewAny(): bool
    {
        $settings = auth()->user()->settings ?? [];
        return $settings['module_academic'] ?? true;
    }
}
