<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Task;
use App\Models\ClassAssignment;
use App\Models\ClassSchedule;
use App\Services\TelegramService;
use Carbon\Carbon;

class TelegramWebhookController extends Controller
{
    protected TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    public function handle(Request $request)
    {
        $update = $request->all();

        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId  = $message['chat']['id'] ?? null;
            $text    = $message['text'] ?? '';

            if (!$chatId) {
                return response()->json(['status' => 'ok']);
            }

            // Cari user berdasarkan chat_id
            /** @var User|null $user */
            $user = User::where('telegram_chat_id', (string) $chatId)->first();

            if (!$user) {
                $this->telegram->sendMessage($chatId, "Maaf, akun Solara Anda belum disinkronkan dengan Chat ID Telegram ini.");
                return response()->json(['status' => 'ok']);
            }

            $keyboard = [
                'keyboard' => [
                    [
                        ['text' => '📋 Cek Tugas & Tasks'],
                        ['text' => '🗓️ Jadwal Hari Ini']
                    ]
                ],
                'resize_keyboard' => true,
                'is_persistent' => true,
            ];

            if ($text === '/start' || strtolower($text) === 'menu' || strtolower($text) === 'halo') {
                $this->telegram->sendMessage(
                    $chatId, 
                    "Halo {$user->name} 👋\n\nSelamat datang di Bot Solara!\nSilakan gunakan menu di bawah untuk memeriksa jadwal atau tugas Anda secara cepat.", 
                    'HTML', 
                    $keyboard
                );
            } elseif ($text === '📋 Cek Tugas & Tasks' || strtolower($text) === '/tasks' || strtolower($text) === '/tugas') {
                $this->sendTasksAndAssignments($chatId, $user, $keyboard);
            } elseif ($text === '🗓️ Jadwal Hari Ini' || strtolower($text) === '/jadwal') {
                $this->sendTodaySchedule($chatId, $user, $keyboard);
            } else {
                $this->telegram->sendMessage(
                    $chatId, 
                    "Perintah tidak dikenali. Silakan gunakan tombol menu di bawah 👇", 
                    'HTML', 
                    $keyboard
                );
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function sendTasksAndAssignments($chatId, User $user, $keyboard)
    {
        // 1. Ambil Tasks yang pending
        $tasks = Task::where('user_id', $user->id)->pending()->get();
        // 2. Ambil Assignments (Tugas Kuliah) yang belum selesai
        $assignments = ClassAssignment::where('user_id', $user->id)->where('status', '!=', 'selesai')->get();

        if ($tasks->isEmpty() && $assignments->isEmpty()) {
            $this->telegram->sendMessage($chatId, "🎉 Hore! Tidak ada tugas atau task yang tertunda saat ini. Anda bisa bersantai!", 'HTML', $keyboard);
            return;
        }

        $msg = "<b>📝 DAFTAR TUGAS & TASKS ANDA</b>\n\n";

        if ($assignments->isNotEmpty()) {
            $msg .= "<b>📚 TUGAS KULIAH:</b>\n";
            foreach ($assignments as $idx => $tugas) {
                $batasWaktu = $tugas->deadline ? $tugas->deadline->format('d M Y') : 'Tanpa batas waktu';
                $icon = $tugas->deadline && $tugas->deadline->isPast() ? '⚠️' : '📖';
                $msg .= "{$icon} <b>{$tugas->title}</b>\n";
                $msg .= "   Batas waktu: {$batasWaktu}\n";
            }
            $msg .= "\n";
        }

        if ($tasks->isNotEmpty()) {
            $msg .= "<b>✅ TO-DO LIST (TASKS):</b>\n";
            foreach ($tasks as $idx => $task) {
                $batasWaktu = clone ($task->due_date ?? now()); // Handle null due date if any, but let's assume due_date is available.
                $batasTgl = $task->due_date ? $task->due_date->format('d M Y') : 'Kapan saja';
                $icon = $task->isOverdue() ? '⚠️' : '🔹';
                $msg .= "{$icon} <b>{$task->title}</b>\n";
                $msg .= "   Status: " . ucfirst($task->status) . " | Due: {$batasTgl}\n";
            }
        }

        $this->telegram->sendMessage($chatId, $msg, 'HTML', $keyboard);
    }

    private function sendTodaySchedule($chatId, User $user, $keyboard)
    {
        $todayStr = now()->locale('id')->isoFormat('dddd, D MMMM YYYY');
        
        $schedules = ClassSchedule::where('user_id', $user->id)->today()->orderBy('waktu_mulai', 'asc')->get();

        if ($schedules->isEmpty()) {
            $this->telegram->sendMessage($chatId, "🗓️ <b>{$todayStr}</b>\n\nWah, hari ini Anda tidak memiliki jadwal kelas/kuliah. Nikmati waktu istirahat Anda! 🏖️", 'HTML', $keyboard);
            return;
        }

        $msg = "🗓️ <b>JADWAL HARI INI</b>\n<i>{$todayStr}</i>\n\n";

        foreach ($schedules as $sched) {
            $jam = substr($sched->waktu_mulai, 0, 5) . ' - ' . substr($sched->waktu_selesai, 0, 5);
            $msg .= "🎓 <b>{$sched->mata_kuliah}</b>\n";
            $msg .= "   ⏰ {$jam}\n";
            $msg .= "   🚪 Ruangan: " . ($sched->ruangan ?? 'Tidak disebutkan') . "\n";
            $msg .= "   👤 Dosen: " . ($sched->dosen ?? 'Tidak disebutkan') . "\n\n";
        }

        $this->telegram->sendMessage($chatId, $msg, 'HTML', $keyboard);
    }
}
