<?php

namespace App\Http\Controllers;

use Google\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GoogleFitController extends Controller
{
    private function getClient()
    {
        $client = new Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        
        // Membaca domain dari APP_URL, dan secara ketat menggunakan awalan https:// untuk Production. 
        // Kenapa? Karena Google OAuth 2.0 menolak redirect URI dengan protokol HTTP.
        $baseUrl = config('app.env') === 'local' ? url('/auth/google/callback') : 'https://gate.syhrulimtkhan.my.id/auth/google/callback';
        $client->setRedirectUri($baseUrl);

        
        // Scope untuk Langkah, Kalori, Tidur, dan Oksigen Tubuh
        $client->addScope([
            'https://www.googleapis.com/auth/fitness.activity.read',
            'https://www.googleapis.com/auth/fitness.sleep.read',
            'https://www.googleapis.com/auth/fitness.body.read', // Untuk detak jantung / SpO2
        ]);
        $client->setAccessType('offline'); // Agar dapat Refresh Token
        $client->setPrompt('consent'); // Agar selalu dipaksa tampil layar consent utk testing
        return $client;
    }

    public function redirect()
    {
        if (empty(env('GOOGLE_CLIENT_ID')) || empty(env('GOOGLE_CLIENT_SECRET'))) {
            return redirect()->back()->with('error', 'Google OAuth belum dikonfigurasi. Tambahkan GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET ke file .env server.');
        }

        $client = $this->getClient();
        return redirect($client->createAuthUrl());
    }

    public function callback(Request $request)
    {
        $client = $this->getClient();
        
        if ($request->has('code')) {
            $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
            
            if (!isset($token['error'])) {
                /** @var \App\Models\User $user */
                $user = auth()->user();
                $user->update([
                    'google_access_token' => $token['access_token'],
                    'google_refresh_token' => $token['refresh_token'] ?? $user->google_refresh_token,
                    'google_token_expires_at' => Carbon::now()->addSeconds($token['expires_in'] ?? 3599),
                ]);
            }
        }

        // Redirect kembali ke Filament Dashboard
        return redirect(url('/app'));
    }
}
