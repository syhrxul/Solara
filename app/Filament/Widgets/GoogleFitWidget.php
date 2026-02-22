<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Google\Client;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GoogleFitWidget extends Widget
{
    protected string $view = 'filament.widgets.google-fit-widget';
    protected int | string | array $columnSpan = 'half';
    protected static ?int $sort = 5;

    public $steps = 0;
    public $calories = 0;
    public $isConnected = false;

    public static function canView(): bool
    {
        return true;
    }

    public function mount()
    {
        $user = auth()->user();
        if ($user->google_access_token) {
            $this->isConnected = true;
            $this->fetchData($user);
        }
    }

    private function getClient($user)
    {
        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        
        $client->setAccessToken([
            'access_token' => $user->google_access_token,
            'refresh_token' => $user->google_refresh_token,
            'expires_in' => $user->google_token_expires_at ? Carbon::parse($user->google_token_expires_at)->diffInSeconds(Carbon::now(), false) * -1 : 0,
        ]);

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                $token = $client->getAccessToken();
                if(!isset($token['error'])){
                    $user->update([
                        'google_access_token' => $token['access_token'],
                        'google_refresh_token' => $token['refresh_token'] ?? $user->google_refresh_token,
                        'google_token_expires_at' => Carbon::now()->addSeconds($token['expires_in'] ?? 3599),
                    ]);
                } else {
                    $this->isConnected = false;
                }
            } else {
                $this->isConnected = false;
            }
        }

        return $client;
    }

    private function fetchData($user)
    {
        try {
            $client = $this->getClient($user);
            
            if (!$this->isConnected) return;

            $token = $client->getAccessToken()['access_token'];
            
            $startTimeMillis = Carbon::now()->startOfDay()->timestamp * 1000;
            $endTimeMillis = Carbon::now()->endOfDay()->timestamp * 1000;

            // Fetch Steps
            $stepsResponse = Http::withToken($token)
                ->post('https://www.googleapis.com/fitness/v1/users/me/dataset:aggregate', [
                    'aggregateBy' => [['dataTypeName' => 'com.google.step_count.delta']],
                    'bucketByTime' => ['durationMillis' => 86400000],
                    'startTimeMillis' => $startTimeMillis,
                    'endTimeMillis' => $endTimeMillis
                ]);

            if ($stepsResponse->successful()) {
                $buckets = $stepsResponse->json('bucket');
                if (!empty($buckets) && isset($buckets[0]['dataset'][0]['point'][0]['value'][0]['intVal'])) {
                    $this->steps = $buckets[0]['dataset'][0]['point'][0]['value'][0]['intVal'];
                }
            }

            // Fetch Calories
            $calResponse = Http::withToken($token)
                ->post('https://www.googleapis.com/fitness/v1/users/me/dataset:aggregate', [
                    'aggregateBy' => [['dataTypeName' => 'com.google.calories.expended']],
                    'bucketByTime' => ['durationMillis' => 86400000],
                    'startTimeMillis' => $startTimeMillis,
                    'endTimeMillis' => $endTimeMillis
                ]);

            if ($calResponse->successful()) {
                $buckets = $calResponse->json('bucket');
                if (!empty($buckets) && isset($buckets[0]['dataset'][0]['point'][0]['value'][0]['fpVal'])) {
                    $this->calories = round($buckets[0]['dataset'][0]['point'][0]['value'][0]['fpVal']);
                }
            }

        } catch (\Exception $e) {
            Log::error('Google Fit Error: ' . $e->getMessage());
        }
    }
}
