<?php

namespace App\Filament\Resources\Goals\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GoalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Goal')
                ->icon('heroicon-o-flag')
                ->schema([
                    TextInput::make('title')
                        ->label('Judul Goal')
                        ->placeholder('Apa yang ingin kamu capai?')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->placeholder('Jelaskan lebih detail goalmu...')
                        ->rows(3)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship(
                                'category',
                                'name',
                                fn ($query) => $query->where('type', 'goal')
                                    ->orWhere('type', 'general')
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'not_started' => '🎯 Belum Mulai',
                                'in_progress' => '🔄 Sedang Berjalan',
                                'completed'   => '✅ Tercapai',
                                'abandoned'   => '🚫 Ditinggalkan',
                            ])
                            ->default('not_started')
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('icon')
                            ->label('Icon')
                            ->placeholder('heroicon-o-flag')
                            ->helperText('Nama icon Heroicon'),

                        ColorPicker::make('color')
                            ->label('Warna')
                            ->default('#f59e0b'),
                    ]),

                    Grid::make(2)->schema([
                        DatePicker::make('target_date')
                            ->label('Target Tanggal')
                            ->native(false)
                            ->displayFormat('d M Y'),

                        Toggle::make('is_pinned')
                            ->label('Sematkan Goal'),
                    ]),
                ]),

            Section::make('Progress')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    TextInput::make('progress')
                        ->label('Progress (%)')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->helperText('0-100%, atau gunakan milestones untuk kalkulasi otomatis'),
                ]),
        ]);
    }
}
