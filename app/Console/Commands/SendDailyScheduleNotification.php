<?php

namespace App\Console\Commands;

use App\Models\ClassSchedule;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class SendDailyScheduleNotification extends Command
{
    protected $signature   = 'solara:notify-schedule {--user= : Specific user ID}';

    protected $description = 'Kirim notifikasi jadwal kuliah hari ini ke semua user';

    public function handle(): int
    {
        $userQuery = User::query();

        if ($userId = $this->option('user')) {
            $userQuery->where('id', $userId);
        }

        $users = $userQuery->get();

        foreach ($users as $user) {
            $schedules = ClassSchedule::today()
                ->where('user_id', $user->id)
                ->orderBy('waktu_mulai')
                ->get();

            if ($schedules->isEmpty()) {
                $this->line("User #{$user->id} ({$user->name}): Tidak ada jadwal hari ini.");
                continue;
            }

            // Build notification body
            $scheduleList = $schedules->map(function ($s) {
                $waktu = substr($s->waktu_mulai, 0, 5);

                return "• {$waktu} — {$s->mata_kuliah} ({$s->kelas})";
            })->join("\n");

            $dayCount = $schedules->count();
            $title = "📅 Jadwal Kuliah Hari Ini ({$dayCount} mata kuliah)";

            // Send Filament database notification
            Notification::make()
                ->title($title)
                ->body($scheduleList)
                ->icon('heroicon-o-academic-cap')
                ->iconColor('violet')
                ->info()
                ->sendToDatabase($user);

            // Send Web Push Notification
            $user->notify(new \App\Notifications\DailyScheduleNotification($title, $scheduleList));

            $this->info("✅ Notifikasi dikirim ke {$user->name}: {$dayCount} jadwal.");
        }

        $this->info('Selesai!');

        return self::SUCCESS;
    }
}
