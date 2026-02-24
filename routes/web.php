<?php

use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

// Redirect root dan semua route lama ke Filament panel
Route::get('/', fn () => redirect('/app'))->name('home');

Route::get('dashboard', fn () => redirect('/app'));

Route::post('/webpush', [PushSubscriptionController::class, 'store']);

Route::get('/auth/google/redirect', [\App\Http\Controllers\GoogleFitController::class, 'redirect'])->middleware('auth');
Route::get('/auth/google/callback', [\App\Http\Controllers\GoogleFitController::class, 'callback'])->middleware('auth');

Route::post('/webhook/telegram', [\App\Http\Controllers\TelegramWebhookController::class, 'handle']);

Route::get('/test-webhook', function () {
    $token = config('services.telegram.bot_token');
    $response = \Illuminate\Support\Facades\Http::get("https://api.telegram.org/bot{$token}/getWebhookInfo");
    return response()->json($response->json());
});

require __DIR__.'/settings.php';
