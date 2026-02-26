<?php

namespace App\Filament\Resources\MonthlyBudgets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MonthlyBudgetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Budget')
                ->schema([
                    Hidden::make('user_id')
                        ->default(fn () => auth()->id()),

                    Select::make('category_id')
                        ->label('Kategori')
                        ->relationship(
                            'category',
                            'name',
                            fn ($query) => $query->where('user_id', auth()->id())->whereIn('type', ['finance', 'general'])
                        )
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('month_year')
                        ->label('Bulan (YYYY-MM)')
                        ->placeholder('Contoh: 2026-02')
                        ->required()
                        ->regex('/^[0-9]{4}-[0-9]{2}$/'),

                    TextInput::make('amount')
                        ->label('Jumlah Budget')
                        ->prefix('Rp')
                        ->numeric()
                        ->required()
                        ->minValue(0),

                    Textarea::make('notes')
                        ->label('Catatan')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }
}
