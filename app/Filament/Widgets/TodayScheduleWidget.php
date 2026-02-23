<?php

namespace App\Filament\Widgets;

use App\Models\ClassSchedule;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TodayScheduleWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $settings = auth()->user()->settings ?? [];
        if (($settings['module_academic'] ?? true) === false) {
            return false;
        }

        return ClassSchedule::today()
            ->where('user_id', auth()->id())
            ->exists();
    }

    protected function getTableHeading(): string
    {
        $days = [
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
            'Sunday'    => 'Minggu',
        ];

        $hari = $days[now()->format('l')] ?? now()->format('l');

        return "📅 Jadwal Kuliah — {$hari}, " . now()->format('d M Y');
    }

    protected function getTableDescription(): ?string
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClassSchedule::query()
                    ->today()
                    ->where('user_id', auth()->id())
                    ->orderBy('waktu_mulai')
            )
            ->columns([
                Tables\Columns\TextColumn::make('waktu_lengkap')
                    ->label('Waktu')
                    ->icon('heroicon-o-clock')
                    ->badge()
                    ->color(fn (ClassSchedule $record): string => $this->getTimeColor($record))
                    ->sortable(['waktu_mulai']),

                Tables\Columns\TextColumn::make('mata_kuliah')
                    ->label('Mata Kuliah')
                    ->weight('bold')
                    ->searchable()
                    ->description(fn (ClassSchedule $record): ?string =>
                        collect([
                            $record->dosen ? "👨‍🏫 {$record->dosen}" : null,
                            $record->ruangan ? "📍 {$record->ruangan}" : null,
                        ])->filter()->implode('  •  ') ?: null
                    ),

                Tables\Columns\TextColumn::make('kelas')
                    ->label('Kelas')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('sks')
                    ->label('SKS')
                    ->alignCenter()
                    ->suffix(' SKS')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('status')
                    ->label('')
                    ->state(fn (ClassSchedule $record): string => $this->getStatusLabel($record))
                    ->badge()
                    ->color(fn (ClassSchedule $record): string => $this->getStatusColor($record)),
            ])
            ->paginated(false)
            ->striped()
            ->emptyStateHeading('Tidak ada kuliah hari ini 🎉')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    private function getTimeColor(ClassSchedule $record): string
    {
        $now = Carbon::now();
        $start = Carbon::parse($record->waktu_mulai);
        $end = $record->waktu_selesai ? Carbon::parse($record->waktu_selesai) : null;

        if ($now->gte($start) && ($end ? $now->lte($end) : true)) {
            return 'success';  // sedang berlangsung
        }

        if ($end ? $now->gt($end) : $now->gt($start->copy()->addHours(2))) {
            return 'gray';     // sudah selesai
        }

        return 'info';         // belum mulai
    }

    private function getStatusLabel(ClassSchedule $record): string
    {
        $now = Carbon::now();
        $start = Carbon::parse($record->waktu_mulai);
        $end = $record->waktu_selesai ? Carbon::parse($record->waktu_selesai) : null;

        if ($now->gte($start) && ($end ? $now->lte($end) : true)) {
            return '🔴 LIVE';
        }

        if ($end ? $now->gt($end) : $now->gt($start->copy()->addHours(2))) {
            return '✅ Selesai';
        }

        return '⏳ Upcoming';
    }

    private function getStatusColor(ClassSchedule $record): string
    {
        $now = Carbon::now();
        $start = Carbon::parse($record->waktu_mulai);
        $end = $record->waktu_selesai ? Carbon::parse($record->waktu_selesai) : null;

        if ($now->gte($start) && ($end ? $now->lte($end) : true)) {
            return 'danger';
        }

        if ($end ? $now->gt($end) : $now->gt($start->copy()->addHours(2))) {
            return 'gray';
        }

        return 'warning';
    }
}
