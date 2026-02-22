<?php

namespace App\Filament\Resources\Goals\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GoalsTable
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
                    ->label('Goal')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->category?->name),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray'    => 'not_started',
                        'info'    => 'in_progress',
                        'success' => 'completed',
                        'danger'  => 'abandoned',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'not_started' => '🎯 Belum Mulai',
                        'in_progress' => '🔄 Berjalan',
                        'completed'   => '✅ Tercapai',
                        'abandoned'   => '🚫 Ditinggalkan',
                        default       => $state,
                    }),

                TextColumn::make('progress')
                    ->label('Progress')
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($record) => match (true) {
                        $record->progress >= 100 => 'success',
                        $record->progress >= 50  => 'warning',
                        default                  => 'gray',
                    }),

                TextColumn::make('target_date')
                    ->label('Target')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar'),
            ])
            ->defaultSort('is_pinned', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'not_started' => 'Belum Mulai',
                        'in_progress' => 'Berjalan',
                        'completed'   => 'Tercapai',
                        'abandoned'   => 'Ditinggalkan',
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
