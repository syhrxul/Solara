<?php

use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

// Redirect root dan semua route lama ke Filament panel
Route::get('/', fn () => redirect('/app'))->name('home');

Route::get('dashboard', fn () => redirect('/app'));

Route::post('/webpush', [PushSubscriptionController::class, 'store']);

require __DIR__.'/settings.php';
