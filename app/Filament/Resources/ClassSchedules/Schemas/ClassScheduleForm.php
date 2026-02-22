<?php

namespace App\Filament\Resources\ClassSchedules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClassScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi Mata Kuliah')
                ->icon('heroicon-o-academic-cap')
                ->schema([
                    TextInput::make('mata_kuliah')
                        ->label('Mata Kuliah')
                        ->placeholder('Contoh: Pemrograman Web')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Grid::make(3)->schema([
                        TextInput::make('kelas')
                            ->label('Kelas')
                            ->placeholder('A / B / C')
                            ->maxLength(50),

                        TextInput::make('sks')
                            ->label('SKS')
                            ->numeric()
                            ->default(2)
                            ->minValue(1)
                            ->maxValue(6),

                        TextInput::make('sesi')
                            ->label('Sesi')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('1, 2, 3...'),
                    ]),

                    TextInput::make('dosen')
                        ->label('Nama Dosen')
                        ->placeholder('Contoh: Dr. Ahmad, M.Kom')
                        ->maxLength(255),

                    Grid::make(2)->schema([
                        Select::make('media_pembelajaran')
                            ->label('Media Pembelajaran')
                            ->options([
                                'Offline'   => '🏫 Offline (Kampus)',
                                'Zoom'      => '💻 Zoom',
                                'Gmeet'     => '💻 Google Meet',
                                'Teams'     => '💻 Microsoft Teams',
                                'Hybrid'    => '🔀 Hybrid',
                                'LMS'       => '📱 LMS/E-Learning',
                            ])
                            ->searchable(),

                        TextInput::make('ruangan')
                            ->label('Ruangan / Link')
                            ->placeholder('Gedung A - 301 atau link zoom')
                            ->maxLength(255),
                    ]),

                    TextInput::make('semester')
                        ->label('Semester')
                        ->placeholder('2024/2025 Ganjil')
                        ->maxLength(100),
                ]),

            Section::make('Jadwal')
                ->icon('heroicon-o-clock')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('hari')
                            ->label('Hari')
                            ->options([
                                'Senin'  => 'Senin',
                                'Selasa' => 'Selasa',
                                'Rabu'   => 'Rabu',
                                'Kamis'  => 'Kamis',
                                'Jumat'  => 'Jumat',
                                'Sabtu'  => 'Sabtu',
                                'Minggu' => 'Minggu',
                            ])
                            ->required(),

                        TimePicker::make('waktu_mulai')
                            ->label('Jam Mulai')
                            ->native(false)
                            ->required(),

                        TimePicker::make('waktu_selesai')
                            ->label('Jam Selesai')
                            ->native(false),
                    ]),

                    Toggle::make('is_active')
                        ->label('Aktif di Semester Ini')
                        ->default(true),
                ]),
        ]);
    }
}
