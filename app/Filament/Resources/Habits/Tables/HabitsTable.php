<?php

namespace App\Filament\Resources\Habits\Tables;

use App\Models\HabitLog;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

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

                IconColumn::make('completed_today')
                    ->label('Hari Ini')
                    ->state(fn ($record) => $record->isCompletedToday())
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

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
                Action::make('toggle_today')
                    ->label(fn ($record) => $record->isCompletedToday() ? 'Batalkan' : 'Selesai Hari Ini')
                    ->icon(fn ($record) => $record->isCompletedToday() ? 'heroicon-o-x-mark' : 'heroicon-o-check')
                    ->color(fn ($record) => $record->isCompletedToday() ? 'danger' : 'success')
                    ->action(function ($record) {
                        $today = today();
                        $log = $record->logs()->where('logged_date', $today)->first();

                        if ($log && $log->completed) {
                            // Un-complete
                            $log->update(['completed' => false, 'count' => 0]);

                            // Recalculate streak
                            $record->update([
                                'current_streak' => max(0, $record->current_streak - 1),
                            ]);

                            Notification::make()
                                ->title('Habit dibatalkan.')
                                ->warning()
                                ->send();
                        } else {
                            // Complete today
                            HabitLog::updateOrCreate(
                                [
                                    'habit_id' => $record->id,
                                    'user_id' => auth()->id(),
                                    'logged_date' => $today,
                                ],
                                [
                                    'completed' => true,
                                    'count' => $record->target_count ?? 1,
                                ]
                            );

                            // Update streak
                            $newStreak = $record->current_streak + 1;
                            $record->update([
                                'current_streak' => $newStreak,
                                'longest_streak' => max($record->longest_streak, $newStreak),
                            ]);

                            Notification::make()
                                ->title('🎉 Habit selesai hari ini!')
                                ->success()
                                ->send();
                        }
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
