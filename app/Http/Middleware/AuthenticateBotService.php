<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts /internal/* routes to the Discord bot process, which
 * authenticates with a single shared bearer token (config('discord.service_token')).
 *
 * See openspec design.md Decision 1 and specs/discord-bot-gateway - "Authenticated internal API access".
 */
class AuthenticateBotService
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var string|null $expected */
        $expected = Config::get('discord.service_token');
        $provided = $request->bearerToken();

        if (blank($expected) || blank($provided) || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
