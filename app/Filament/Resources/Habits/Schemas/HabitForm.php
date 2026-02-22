<?php

namespace App\Filament\Resources\Habits\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HabitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Kebiasaan')
                ->icon('heroicon-o-fire')
                ->schema([
                    TextInput::make('name')
                        ->label('Nama Kebiasaan')
                        ->placeholder('Contoh: Olahraga pagi, Baca buku, Minum air...')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->placeholder('Kenapa kebiasaan ini penting untukmu?')
                        ->rows(2)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        TextInput::make('icon')
                            ->label('Icon (Heroicon)')
                            ->placeholder('heroicon-o-fire')
                            ->helperText('Gunakan nama icon dari heroicons.com'),

                        ColorPicker::make('color')
                            ->label('Warna')
                            ->default('#10b981'),
                    ]),
                ]),

            Section::make('Pengaturan')
                ->icon('heroicon-o-cog-6-tooth')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('frequency')
                            ->label('Frekuensi')
                            ->options([
                                'daily'   => 'Setiap Hari',
                                'weekly'  => 'Setiap Minggu',
                                'monthly' => 'Setiap Bulan',
                            ])
                            ->default('daily')
                            ->required(),

                        TextInput::make('target_count')
                            ->label('Target Jumlah')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->suffix(fn ($get) => $get('unit') ?: 'kali'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('unit')
                            ->label('Satuan')
                            ->placeholder('Contoh: gelas, menit, halaman')
                            ->helperText('Opsional, satuan untuk target'),

                        TimePicker::make('reminder_time')
                            ->label('Waktu Pengingat')
                            ->native(false),
                    ]),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->helperText('Nonaktifkan jika sedang tidak ditracking'),
                ]),
        ]);
    }
}
