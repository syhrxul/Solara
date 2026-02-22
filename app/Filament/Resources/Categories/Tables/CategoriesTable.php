<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('')
                    ->width('40px'),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->weight('medium'),

                BadgeColumn::make('type')
                    ->label('Tipe')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'task'    => '📋 Task',
                        'note'    => '📝 Catatan',
                        'finance' => '💰 Keuangan',
                        'goal'    => '🎯 Goal',
                        'general' => '🌐 Umum',
                        default   => $state,
                    }),

                TextColumn::make('tasks_count')
                    ->label('Tasks')
                    ->counts('tasks')
                    ->sortable(),

                TextColumn::make('notes_count')
                    ->label('Catatan')
                    ->counts('notes')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'task'    => 'Task',
                        'note'    => 'Catatan',
                        'finance' => 'Keuangan',
                        'goal'    => 'Goal',
                        'general' => 'Umum',
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
