<?php

namespace App\Filament\Resources\Tasks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('is_pinned')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-s-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->width('40px'),

                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->category?->name),

                BadgeColumn::make('priority')
                    ->label('Prioritas')
                    ->colors([
                        'success' => 'low',
                        'warning' => 'medium',
                        'danger'  => 'high',
                        'gray'    => 'urgent',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'low'    => '🟢 Rendah',
                        'medium' => '🟡 Sedang',
                        'high'   => '🔴 Tinggi',
                        'urgent' => '🚨 Mendesak',
                        default  => $state,
                    }),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info'    => 'in_progress',
                        'success' => 'completed',
                        'danger'  => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'pending'     => '⏳ Menunggu',
                        'in_progress' => '🔄 Dikerjakan',
                        'completed'   => '✅ Selesai',
                        'cancelled'   => '❌ Dibatalkan',
                        default       => $state,
                    }),

                TextColumn::make('due_date')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : 'gray'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('is_pinned', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'     => 'Menunggu',
                        'in_progress' => 'Dikerjakan',
                        'completed'   => 'Selesai',
                        'cancelled'   => 'Dibatalkan',
                    ]),
                SelectFilter::make('priority')
                    ->label('Prioritas')
                    ->options([
                        'low'    => 'Rendah',
                        'medium' => 'Sedang',
                        'high'   => 'Tinggi',
                        'urgent' => 'Mendesak',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
