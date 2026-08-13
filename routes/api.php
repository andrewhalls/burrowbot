<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal Bot API Routes
|--------------------------------------------------------------------------
|
| Consumed exclusively by the Burrow Discord gateway bot process (bot/).
| Every route here sits behind the "bot.auth" middleware (bearer
| BOT_SERVICE_TOKEN) - see App\Http\Middleware\AuthenticateBotService.
| Contract documented in openapi.yaml at the repo root.
|
*/

Route::prefix('internal')
    ->middleware(['api', 'bot.auth'])
    ->group(base_path('routes/internal.php'));
