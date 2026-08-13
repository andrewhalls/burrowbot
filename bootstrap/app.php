<?php

use App\Http\Middleware\AuthenticateBotService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // Deliberately not using the `api:` shorthand: it auto-prefixes
        // routes with `/api`, but the internal bot contract (openapi.yaml)
        // lives at `/internal/*` directly. routes/api.php applies the same
        // "api" middleware group (rate limiting, stateless) itself.
        then: function () {
            require __DIR__.'/../routes/api.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'bot.auth' => AuthenticateBotService::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('internal/*'),
        );
    })->create();
