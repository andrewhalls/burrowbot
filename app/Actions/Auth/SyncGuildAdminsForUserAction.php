<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;

/**
 * Reconciles a user's guild_admins rows against the guilds Discord reports
 * them as an admin of right now. Called on every login, so a role revoked
 * in Discord is revoked here too on the user's next login.
 *
 * See openspec specs/auth - "Authorization revoked upstream".
 */
class SyncGuildAdminsForUserAction
{
    /**
     * @param  array<string, bool>  $administeredDiscordGuildIds  discord_guild_id => is admin, from DiscordUserGuildsClient
     */
    public function execute(User $user, array $administeredDiscordGuildIds): void
    {
        $stillAdminOfGuildIds = Guild::query()
            ->whereIn('discord_guild_id', array_keys(array_filter($administeredDiscordGuildIds)))
            ->pluck('id');

        foreach ($stillAdminOfGuildIds as $guildId) {
            GuildAdmin::query()->firstOrCreate([
                'guild_id' => $guildId,
                'user_id' => $user->id,
            ], ['role' => 'admin']);
        }

        GuildAdmin::query()
            ->where('user_id', $user->id)
            ->whereNotIn('guild_id', $stillAdminOfGuildIds)
            ->delete();
    }
}
