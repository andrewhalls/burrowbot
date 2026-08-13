<?php

declare(strict_types=1);

namespace App\Services\Discord;

use App\Support\Discord\GuildPermissions;
use Illuminate\Support\Facades\Http;

/**
 * Fetches the Discord guilds a user belongs to, via the OAuth access token
 * issued during login (requires the "guilds" scope), and reduces the
 * response to "discord_guild_id => is admin" for SyncGuildAdminsForUserAction.
 */
class DiscordUserGuildsClient
{
    /**
     * @return array<string, bool> discord_guild_id => whether the user has
     *                              admin-level permissions in that guild
     */
    public function administeredGuildIds(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://discord.com/api/users/@me/guilds')
            ->throw();

        $result = [];

        foreach ($response->json() ?? [] as $guild) {
            $result[(string) $guild['id']] = GuildPermissions::grantsGuildAdmin($guild['permissions'] ?? 0);
        }

        return $result;
    }
}
