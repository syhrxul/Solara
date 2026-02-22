<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Kategori')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Kategori')
                        ->required()
                        ->maxLength(255),

                    Grid::make(2)->schema([
                        Select::make('type')
                            ->label('Tipe')
                            ->options([
                                'task'    => '📋 Task',
                                'note'    => '📝 Catatan',
                                'finance' => '💰 Keuangan',
                                'goal'    => '🎯 Goal',
                                'general' => '🌐 Umum',
                            ])
                            ->required()
                            ->default('general'),

                        ColorPicker::make('color')
                            ->label('Warna')
                            ->default('#6366f1'),
                    ]),

                    TextInput::make('icon')
                        ->label('Icon Heroicon')
                        ->placeholder('heroicon-o-tag')
                        ->helperText('Nama icon dari heroicons.com'),
                ]),
        ]);
    }
}
