<?php

namespace App\Filament\Widgets;

use App\Models\ClassSchedule;
use Filament\Widgets\Widget;

class TodayScheduleWidget extends Widget
{
    protected string $view = 'filament.widgets.today-schedule';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public $scheduleList;

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

    public function mount(): void
    {
        $this->scheduleList = ClassSchedule::today()
            ->where('user_id', auth()->id())
            ->orderBy('waktu_mulai')
            ->get();
    }

    public function getTodayName(): string
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

        return $days[now()->format('l')] . ', ' . now()->format('d F Y');
    }


}
