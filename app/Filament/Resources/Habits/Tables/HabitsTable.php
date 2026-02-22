<?php

namespace App\Filament\Resources\Habits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class HabitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Kebiasaan')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->description),

                BadgeColumn::make('frequency')
                    ->label('Frekuensi')
                    ->colors([
                        'success' => 'daily',
                        'info'    => 'weekly',
                        'warning' => 'monthly',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'daily'   => 'Harian',
                        'weekly'  => 'Mingguan',
                        'monthly' => 'Bulanan',
                        default   => $state,
                    }),

                TextColumn::make('current_streak')
                    ->label('🔥 Streak')
                    ->suffix(fn ($record) => ' hari')
                    ->sortable()
                    ->color('warning'),

                TextColumn::make('longest_streak')
                    ->label('🏆 Rekor')
                    ->suffix(' hari')
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                SelectFilter::make('frequency')
                    ->label('Frekuensi')
                    ->options([
                        'daily'   => 'Harian',
                        'weekly'  => 'Mingguan',
                        'monthly' => 'Bulanan',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
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
