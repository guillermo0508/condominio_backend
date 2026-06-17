<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'auth/*',
            'chat/*',
            'admin/*',
            'notifications/*',
            'api/*',
        ]);

        $middleware->trustHosts(at: [
            '127.0.0.1:8000',
            'localhost:8000',
            'localhost:5173',
            '127.0.0.1:5173',
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\CheckAdminRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

    })->create();
