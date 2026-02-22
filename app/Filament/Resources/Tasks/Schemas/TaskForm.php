<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Models\Category;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Task Details')
                ->icon('heroicon-o-clipboard-document-list')
                ->description('Isi detail tugas yang ingin kamu selesaikan')
                ->schema([
                    TextInput::make('title')
                        ->label('Judul Tugas')
                        ->placeholder('Apa yang perlu kamu lakukan?')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->placeholder('Tambahkan detail...')
                        ->rows(3)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship(
                                'category',
                                'name',
                                fn ($query) => $query->where('type', 'task')
                                    ->orWhere('type', 'general')
                            )
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('type')
                                    ->options(['task' => 'Task', 'general' => 'General'])
                                    ->default('task'),
                            ])
                            ->nullable(),

                        Select::make('priority')
                            ->label('Prioritas')
                            ->options([
                                'low'    => '🟢 Rendah',
                                'medium' => '🟡 Sedang',
                                'high'   => '🔴 Tinggi',
                                'urgent' => '🚨 Mendesak',
                            ])
                            ->default('medium')
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending'     => '⏳ Menunggu',
                                'in_progress' => '🔄 Dikerjakan',
                                'completed'   => '✅ Selesai',
                                'cancelled'   => '❌ Dibatalkan',
                            ])
                            ->default('pending')
                            ->required(),

                        Toggle::make('is_pinned')
                            ->label('Sematkan Tugas')
                            ->helperText('Tugas penting yang ingin selalu terlihat'),
                    ]),
                ]),

            Section::make('Jadwal')
                ->icon('heroicon-o-calendar-days')
                ->description('Atur deadline tugas')
                ->schema([
                    Grid::make(2)->schema([
                        DatePicker::make('due_date')
                            ->label('Tanggal Deadline')
                            ->native(false)
                            ->displayFormat('d M Y'),

                        TimePicker::make('due_time')
                            ->label('Waktu Deadline')
                            ->native(false),
                    ]),
                ]),
        ]);
    }
}
