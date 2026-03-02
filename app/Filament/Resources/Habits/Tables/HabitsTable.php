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
                    ->formatStateUsing(function ($record) {
                        $streak = $record->current_streak;
                        if ($streak == 0 && $record->canRestoreStreak()) {
                            return "💔 Putus ({$record->streak_before_break} hari)";
                        }
                        return "{$streak} hari";
                    })
                    ->sortable()
                    ->color(fn ($record) => $record->current_streak == 0 && $record->canRestoreStreak() ? 'danger' : 'warning'),

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
                // Tombol restore streak (hanya tampil jika streak bisa di-restore)
                Action::make('restore_streak')
                    ->label('♻️ Restore Streak')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn ($record) => $record->canRestoreStreak() && !$record->isCompletedToday())
                    ->requiresConfirmation()
                    ->modalHeading('Restore Streak?')
                    ->modalDescription(fn ($record) => "Anda akan me-restore streak {$record->streak_before_break} hari untuk \"{$record->name}\". Streak Anda yang lama akan dikembalikan, namun hari ini tidak dihitung sebagai tambahan streak — akan dihitung pada completion berikutnya.")
                    ->action(function ($record) {
                        if ($record->restoreStreak()) {
                            // Buat log hari ini sebagai "restore" (completed, tapi streak tidak bertambah)
                            HabitLog::updateOrCreate(
                                [
                                    'habit_id'    => $record->id,
                                    'user_id'     => auth()->id(),
                                    'logged_date' => today(),
                                ],
                                [
                                    'completed' => true,
                                    'count'     => $record->target_count ?? 1,
                                    'notes'     => 'Streak restored',
                                ]
                            );

                            Notification::make()
                                ->title("♻️ Streak berhasil di-restore!")
                                ->body("Streak {$record->current_streak} hari untuk \"{$record->name}\" telah dikembalikan. Streak baru akan dihitung pada completion berikutnya.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Gagal restore streak')
                                ->body('Waktu restore sudah lewat 24 jam.')
                                ->danger()
                                ->send();
                        }
                    }),

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

                            // Cek apakah ini restore (notes = 'Streak restored')
                            // Jika ini adalah hari restore, jangan tambah streak
                            $isRestoreDay = $log && $log->notes === 'Streak restored';

                            if ($isRestoreDay) {
                                // Hari ini sudah dipakai untuk restore, tidak tambah streak
                                Notification::make()
                                    ->title('✅ Habit selesai!')
                                    ->body('Streak tidak bertambah karena hari ini adalah hari restore.')
                                    ->success()
                                    ->send();
                            } else {
                                // Update streak
                                $newStreak = $record->current_streak + 1;
                                $record->update([
                                    'current_streak'      => $newStreak,
                                    'longest_streak'      => max($record->longest_streak, $newStreak),
                                    'last_completed_date' => $today,
                                    'streak_broken_at'    => null,
                                    'streak_before_break' => 0,
                                ]);

                                Notification::make()
                                    ->title('🎉 Habit selesai hari ini!')
                                    ->body("Streak saat ini: {$newStreak} hari 🔥")
                                    ->success()
                                    ->send();
                            }
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
