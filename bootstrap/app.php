<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        // API-only: redirect unauthenticated ke JSON response
        $middleware->redirectGuestsTo(fn () => response()->json([
            'success' => false,
            'message' => 'Unauthenticated. Silakan login terlebih dahulu.',
        ], 401));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Return JSON for all exceptions (API-only)
        $exceptions->shouldRenderJsonWhen(fn () => true);
    })->create();
