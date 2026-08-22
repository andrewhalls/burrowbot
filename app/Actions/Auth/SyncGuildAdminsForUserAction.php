<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;

/**
 * Reconciles a user's guild_admins rows against the guilds Discord reports
 * them as a member/admin of right now. Called on every login, so a role
 * revoked in Discord is revoked here too on the user's next login.
 *
 * Only ever creates, upgrades, or revokes `discord_sync` (full-admin) rows
 * from Discord's reported permissions - a `granted` (scoped) row, created
 * separately via the Admins dashboard screen, is left untouched here except
 * being revoked if the user has left the Discord guild entirely, or being
 * upgraded to `discord_sync` if the user has since become a real Discord
 * admin of that guild.
 *
 * See openspec specs/auth - "Authorization revoked upstream", "A pending
 * scoped admin grant resolves on first login", "Scoped admin access ends
 * when guild membership ends"; design.md Decision 5 (add-guild-admin-permissions).
 */
class SyncGuildAdminsForUserAction
{
    /**
     * @param  array<string, bool>  $discordGuildAdminFlags  discord_guild_id => is admin, from DiscordUserGuildsClient (every guild the user belongs to, not only the ones they administer)
     */
    public function execute(User $user, array $discordGuildAdminFlags): void
    {
        // Resolve any grant created for this Discord user before they had
        // ever logged into Burrow (design.md Decision 3).
        if ($user->discord_user_id !== null) {
            GuildAdmin::query()
                ->where('discord_user_id', $user->discord_user_id)
                ->whereNull('user_id')
                ->update(['user_id' => $user->id]);
        }

        $memberGuildIds = Guild::query()
            ->whereIn('discord_guild_id', array_keys($discordGuildAdminFlags))
            ->pluck('id', 'discord_guild_id');

        $fullAdminGuildIds = $memberGuildIds
            ->only(array_keys(array_filter($discordGuildAdminFlags)))
            ->values();

        foreach ($fullAdminGuildIds as $guildId) {
            GuildAdmin::query()->updateOrCreate(
                ['guild_id' => $guildId, 'user_id' => $user->id],
                ['source' => GuildAdmin::SOURCE_DISCORD_SYNC, 'sections' => null],
            );
        }

        // Revoke full-admin access the user no longer holds in Discord.
        GuildAdmin::query()
            ->where('user_id', $user->id)
            ->where('source', GuildAdmin::SOURCE_DISCORD_SYNC)
            ->whereNotIn('guild_id', $fullAdminGuildIds)
            ->delete();

        // Revoke a granted (scoped) admin's access only once the user has
        // left the Discord guild entirely - not merely lost an elevated
        // Discord permission while remaining a member.
        GuildAdmin::query()
            ->where('user_id', $user->id)
            ->where('source', GuildAdmin::SOURCE_GRANTED)
            ->whereNotIn('guild_id', $memberGuildIds->values())
            ->delete();
    }
}
