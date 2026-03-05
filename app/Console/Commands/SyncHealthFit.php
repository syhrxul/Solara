<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\HealthMetric;
use Google\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class SyncHealthFit extends Command
{
    protected $signature = 'health:sync {--user= : ID user tertentu}';
    protected $description = 'Sync health data from Google Fit for users';

    public function handle()
    {
        $query = User::whereNotNull('google_refresh_token');
        if ($userId = $this->option('user')) {
            $query->where('id', $userId);
        }

        $users = $query->get();

        foreach ($users as $user) {
            $this->info("Syncing user: {$user->name}");
            $this->syncUser($user);
        }

        $this->info("Sync completed.");
    }

    private function syncUser(User $user)
    {
        try {
            $client = new Client();
            $client->setClientId(env('GOOGLE_CLIENT_ID'));
            $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
            
            // Check if token expired
            if (Carbon::parse($user->google_token_expires_at)->isPast()) {
                $token = $client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);
                if (!isset($token['error'])) {
                    $user->update([
                        'google_access_token' => $token['access_token'],
                        'google_token_expires_at' => Carbon::now()->addSeconds($token['expires_in'] ?? 3599),
                    ]);
                } else {
                    $this->error("Failed to refresh token for user {$user->name}");
                    return;
                }
            }

            $accessToken = $user->google_access_token;
            $this->syncSleep($user, $accessToken);
            // Tambahkan sync langkah atau kalori selanjutnya jika diperlukan
            
        } catch (\Exception $e) {
            $this->error("Error syncing user {$user->name}: " . $e->getMessage());
        }
    }

    private function syncSleep(User $user, $accessToken)
    {
        // Ambil data 7 hari terakhir
        $startTimeMillis = Carbon::now()->subDays(7)->timestamp * 1000;
        $endTimeMillis = Carbon::now()->timestamp * 1000;

        $response = Http::withToken($accessToken)
            ->get("https://www.googleapis.com/fitness/v1/users/me/sessions", [
                'startTime' => Carbon::now()->subDays(7)->toIso8601String(),
                'endTime' => Carbon::now()->toIso8601String(),
                'activityType' => 72 // Sleep
            ]);

        if ($response->successful()) {
            $sessions = $response->json('session', []);
            
            \App\Models\HealthMetric::where('user_id', $user->id)
                ->where('type', 'sleep')
                ->where('date', '>=', Carbon::now()->subDays(7)->toDateString())
                ->delete();

            $intervals = [];
            foreach ($sessions as $session) {
                $intervals[] = [
                    'start' => $session['startTimeMillis'],
                    'end' => $session['endTimeMillis']
                ];
            }

            usort($intervals, function($a, $b) {
                if ($a['start'] == $b['start']) return $b['end'] <=> $a['end'];
                return $a['start'] <=> $b['start'];
            });

            $merged = [];
            foreach ($intervals as $inv) {
                if (empty($merged)) {
                    $merged[] = $inv;
                } else {
                    $last = &$merged[count($merged)-1];
                    if ($inv['start'] <= $last['end']) {
                        $last['end'] = max($last['end'], $inv['end']);
                    } else {
                        $merged[] = $inv;
                    }
                }
            }

            $dailySleep = [];
            foreach ($merged as $inv) {
                $startMillis = $inv['start'];
                $endMillis = $inv['end'];
                
                $start = Carbon::createFromTimestampMs($startMillis)->timezone('Asia/Jakarta');
                $end = Carbon::createFromTimestampMs($endMillis)->timezone('Asia/Jakarta');
                
                $date = $end->toDateString();
                $hours = $start->diffInMinutes($end) / 60;
                
                if (!isset($dailySleep[$date])) {
                    $dailySleep[$date] = [
                        'value' => 0,
                        'start' => $start,
                        'end' => $end,
                        'startMillis' => $startMillis,
                        'endMillis' => $endMillis,
                    ];
                }
                
                $dailySleep[$date]['value'] += $hours;
                
                if ($start->lt($dailySleep[$date]['start'])) {
                    $dailySleep[$date]['start'] = $start;
                    $dailySleep[$date]['startMillis'] = $startMillis;
                }
                if ($end->gt($dailySleep[$date]['end'])) {
                    $dailySleep[$date]['end'] = $end;
                    $dailySleep[$date]['endMillis'] = $endMillis;
                }
            }

            foreach ($dailySleep as $date => $data) {
                $hours = $data['value'];
                $score = $hours >= 7 ? 'Sangat Baik' : ($hours >= 6 ? 'Cukup' : 'Kurang');

                HealthMetric::create([
                    'user_id' => $user->id,
                    'type' => 'sleep',
                    'date' => $date,
                    'value' => round($hours, 2),
                    'details' => [
                        'score' => $score,
                        'time_bed' => $data['start']->format('H:i'),
                        'time_wakeup' => $data['end']->format('H:i'),
                        'start_timestamp' => $data['startMillis'],
                        'end_timestamp' => $data['endMillis'],
                    ]
                ]);
            }
        }
    }
}
