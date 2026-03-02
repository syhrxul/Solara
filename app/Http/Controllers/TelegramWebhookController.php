<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Task;
use App\Models\ClassAssignment;
use App\Models\ClassSchedule;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        if (isset($update['callback_query'])) {
            $message = $update['callback_query']['message'];
            $chatId  = $message['chat']['id'] ?? null;
            $text    = $update['callback_query']['data'] ?? ''; 
            $callbackQueryId = $update['callback_query']['id'];
            
            Log::info("Telegram Callback Query: " . $text);
            $this->telegram->answerCallbackQuery($callbackQueryId);
            
        } elseif (isset($update['message'])) {
            $message = $update['message'];
            $chatId  = $message['chat']['id'] ?? null;
            $text    = $message['text'] ?? '';
            Log::info("Telegram Message: " . $text);
        } else {
            return response()->json(['status' => 'ok']);
        }

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
                    ['text' => '📋 Tugas'],
                    ['text' => '🗓️ Jadwal'],
                    ['text' => '🔄 Habits']
                ]
            ],
            'resize_keyboard' => true,
            'is_persistent' => true,
        ];

        $lowText = strtolower(trim($text));

        if ($lowText === '/start' || $lowText === 'menu' || $lowText === 'halo') {
            $this->telegram->sendMessage(
                $chatId, 
                "Halo {$user->name} 👋\n\nSelamat datang di Bot Solara!\nSilakan gunakan menu di bawah untuk memeriksa jadwal, tugas, dan habits Anda secara cepat.", 
                'HTML', 
                $keyboard
            );
        } elseif (in_array($lowText, ['📋 tugas', '/task', '/tasks', '/tugas', 'tugas', 'tugas kuliah', 'tasks'])) {
            $this->sendTasksAndAssignments($chatId, $user, $keyboard);
        } elseif (in_array($lowText, ['🗓️ jadwal', '/jadwal', 'jadwal', 'jadwal kuliah', '/schedule'])) {
            $this->sendTodaySchedule($chatId, $user, $keyboard);
        } elseif (in_array($lowText, ['🔄 habits', '/habits', '/habit', 'habbit', 'habit', 'habits'])) {
            $this->sendTodayHabits($chatId, $user, $keyboard);
        } elseif (str_starts_with($lowText, 'chk_hab_')) {
            $habitId = str_replace('chk_hab_', '', $lowText);
            $this->markHabitComplete($chatId, $user, $habitId, $message['message_id']);
        } elseif (str_starts_with($lowText, 'restore_hab_')) {
            $habitId = str_replace('restore_hab_', '', $lowText);
            $this->restoreHabitStreak($chatId, $user, $habitId, $message['message_id']);
        } else {
            $this->telegram->sendMessage(
                $chatId, 
                "Perintah tidak dikenali. Silakan gunakan tombol menu di bawah 👇", 
                'HTML',
                $keyboard
            );
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
            $assignWithDeadline = $assignments->filter(function($a) { return !empty($a->deadline); });
            $assignWithout = $assignments->filter(function($a) { return empty($a->deadline); });

            foreach ($assignWithDeadline as $idx => $tugas) {
                $batasWaktu = $tugas->deadline->format('d M Y');
                $icon = $tugas->deadline->isPast() ? '⚠️' : '📖';
                $msg .= "{$icon} <b>{$tugas->title}</b>\n";
                $msg .= "   Batas waktu: {$batasWaktu}\n";
            }

            if ($assignWithout->isNotEmpty()) {
                if ($assignWithDeadline->isNotEmpty()) {
                    $msg .= "➖ <i>Tanpa Batas Waktu:</i> ➖\n";
                } else {
                    $msg .= "➖ <i>Tanpa Batas Waktu:</i> ➖\n";
                }
                
                foreach ($assignWithout as $idx => $tugas) {
                    $msg .= "📖 <b>{$tugas->title}</b>\n";
                }
            }
            $msg .= "\n";
        }

        if ($tasks->isNotEmpty()) {
            $msg .= "<b>✅ TO-DO LIST (TASKS):</b>\n";
            $tasksWithDeadline = $tasks->filter(function($t) { return !empty($t->due_date); });
            $tasksWithout = $tasks->filter(function($t) { return empty($t->due_date); });

            foreach ($tasksWithDeadline as $idx => $task) {
                $batasTgl = $task->due_date->format('d M Y');
                $icon = $task->isOverdue() ? '⚠️' : '🔹';
                $msg .= "{$icon} <b>{$task->title}</b>\n";
                $msg .= "   Status: " . ucfirst($task->status) . " | Due: {$batasTgl}\n";
            }

            if ($tasksWithout->isNotEmpty()) {
                if ($tasksWithDeadline->isNotEmpty()) {
                    $msg .= "➖ <i>Tanpa Batas Waktu:</i> ➖\n";
                } else {
                    $msg .= "➖ <i>Tanpa Batas Waktu:</i> ➖\n";
                }
                
                foreach ($tasksWithout as $idx => $task) {
                    $msg .= "🔹 <b>{$task->title}</b>\n";
                    $msg .= "   Status: " . ucfirst($task->status) . "\n";
                }
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

    private function sendTodayHabits($chatId, User $user, $keyboard)
    {
        $now = now();
        $habits = Habit::where('user_id', $user->id)
            ->where('is_active', true)
            ->get();

        if ($habits->isEmpty()) {
            $msg = "Anda belum merencanakan Habits (Kebiasaan) apapun. Mari bangun kebiasaan yang baik!";
            $this->telegram->sendMessage($chatId, $msg, 'HTML', $keyboard);
            return;
        }

        $this->telegram->sendMessage($chatId, "🔄 <b>PANTAUAN HABITS ANDA HARI INI</b>\nBerikut adalah deretan Habit yang perlu Anda kerjakan:", 'HTML', $keyboard);

        $hasTarget = false;

        foreach ($habits as $habit) {
            if (!$habit->shouldDoOnDay()) {
                continue;
            }

            $hasTarget = true;
            $isDone = $habit->isCompletedToday();

            if ($isDone) {
                $msg = "✅ <s><b>{$habit->name}</b></s>\nAnda sudah menyelesaikan ini hari ini! Streak: <b>{$habit->current_streak} 🔥</b>";
                $this->telegram->sendMessage($chatId, $msg, 'HTML');
            } elseif ($habit->canRestoreStreak()) {
                // Streak putus, bisa di-restore
                $msg = "💔 <b>{$habit->name}</b>\n\n";
                $msg .= "Streak <b>{$habit->streak_before_break} hari</b> Anda telah putus!\n";
                $msg .= "⏳ Anda masih bisa restore dalam waktu terbatas.\n\n";
                $msg .= "Setelah restore, streak lama akan dikembalikan.\nHari ini tidak dihitung—dihitung pada completion berikutnya.";
                
                $inlineBtn = [
                    'inline_keyboard' => [
                        [
                            ['text' => '♻️ Restore Streak', 'callback_data' => 'restore_hab_' . $habit->id]
                        ],
                        [
                            ['text' => '✅ Mulai Streak Baru', 'callback_data' => 'chk_hab_' . $habit->id]
                        ]
                    ]
                ];
                $this->telegram->sendMessage($chatId, $msg, 'HTML', $inlineBtn);
            } else {
                $msg = "⭕ <b>{$habit->name}</b>\n\nYuk jalankan segera untuk mempertahankan Streak Anda: <b>{$habit->current_streak} 🔥</b>";
                $inlineBtn = [
                    'inline_keyboard' => [
                        [
                            ['text' => '✅ Tandai Selesai', 'callback_data' => 'chk_hab_' . $habit->id]
                        ]
                    ]
                ];
                $this->telegram->sendMessage($chatId, $msg, 'HTML', $inlineBtn);
            }
        }

        if (!$hasTarget) {
            $msg = "🎉 <b>Yeay!</b> Tidak ada target habit spesifik yang harus diselesaikan untuk hari ini. Waktu santai yang berkualitas untuk Anda!";
            $this->telegram->sendMessage($chatId, $msg, 'HTML', $keyboard);
        }
    }

    private function markHabitComplete($chatId, User $user, $habitId, $messageId)
    {
        Log::info("Marking habit complete for {$habitId}");
        $habit = Habit::where('id', $habitId)->where('user_id', $user->id)->first();
        if (!$habit) {
            $this->telegram->editMessageText($chatId, $messageId, "Habit tidak ditemukan.", 'HTML');
            return;
        }

        if ($habit->isCompletedToday()) {
            Log::info("Habit already completed.");
            $this->telegram->editMessageText($chatId, $messageId, "✅ <b>{$habit->name}</b> sudah diselesaikan!", 'HTML');
            dispatch(function () use ($chatId, $messageId) {
                sleep(3);
                $telegram = new TelegramService();
                $telegram->deleteMessage((string)$chatId, (int)$messageId);
            })->afterResponse();
        } else {
            Log::info("Habit not yet completed via DB.");
            HabitLog::updateOrCreate(
                [
                    'habit_id' => $habit->id,
                    'user_id' => $user->id,
                    'logged_date' => today(),
                ],
                [
                    'completed' => true,
                    'count' => $habit->target_count ?? 1,
                ]
            );

            // Update streak dan tracking
            $newStreak = $habit->current_streak + 1;
            $habit->update([
                'current_streak'      => $newStreak,
                'longest_streak'      => max($habit->longest_streak, $newStreak),
                'last_completed_date' => today(),
                'streak_broken_at'    => null,
                'streak_before_break' => 0,
            ]);

            $motivationalMessages = [
                "Luar biasa! 🔥",
                "Keren banget! 🚀",
                "Mantap jiwa! 💪",
                "Fantastic! Jangan kasih kendor! 🌟",
                "Sempurna! Terus bertumbuh! 🌱"
            ];
            $motivasi = $motivationalMessages[array_rand($motivationalMessages)];

            $newMsg = "{$motivasi}\n✅ <b>{$habit->name}</b> berhasil diselesaikan!\n\nStreak saat ini memanjang jadi: <b>{$newStreak} 🔥</b>\n\n<i>Pesan ini akan otomatis dihapus dalam 5 detik...</i>";
            
            $this->telegram->editMessageText($chatId, $messageId, $newMsg, 'HTML');
            
            dispatch(function () use ($chatId, $messageId) {
                sleep(5);
                $telegram = new TelegramService();
                $telegram->deleteMessage((string)$chatId, (int)$messageId);
            })->afterResponse();
        }
    }

    private function restoreHabitStreak($chatId, User $user, $habitId, $messageId)
    {
        Log::info("Restoring habit streak for {$habitId}");
        $habit = Habit::where('id', $habitId)->where('user_id', $user->id)->first();
        
        if (!$habit) {
            $this->telegram->editMessageText($chatId, $messageId, "Habit tidak ditemukan.", 'HTML');
            return;
        }

        if (!$habit->canRestoreStreak()) {
            $this->telegram->editMessageText($chatId, $messageId, "⏳ Waktu restore sudah habis. Streak tidak bisa dipulihkan.", 'HTML');
            return;
        }

        $oldStreakValue = $habit->streak_before_break;

        // Restore streak
        $habit->restoreStreak();

        // Buat log sebagai restore entry (hari ini tidak menambah streak)
        HabitLog::updateOrCreate(
            [
                'habit_id'    => $habit->id,
                'user_id'     => $user->id,
                'logged_date' => today(),
            ],
            [
                'completed' => true,
                'count'     => $habit->target_count ?? 1,
                'notes'     => 'Streak restored',
            ]
        );

        $newMsg = "♻️ <b>Streak Restored!</b>\n\n";
        $newMsg .= "✅ <b>{$habit->name}</b>\n";
        $newMsg .= "Streak <b>{$oldStreakValue} hari</b> berhasil dipulihkan! 🔥\n\n";
        $newMsg .= "<i>Catatan: Hari ini tidak dihitung sebagai tambahan streak. Streak baru akan bertambah pada completion berikutnya.</i>\n\n";
        $newMsg .= "<i>Pesan ini akan otomatis dihapus dalam 7 detik...</i>";

        $this->telegram->editMessageText($chatId, $messageId, $newMsg, 'HTML');

        dispatch(function () use ($chatId, $messageId) {
            sleep(7);
            $telegram = new TelegramService();
            $telegram->deleteMessage((string)$chatId, (int)$messageId);
        })->afterResponse();
    }
}
