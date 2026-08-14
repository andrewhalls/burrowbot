<?php

declare(strict_types=1);

namespace App\Actions\Discord;

use App\Models\DiscordRole;
use App\Models\Guild;

/**
 * Replaces a guild's entire synced role list with exactly what's given -
 * upserts everything present, deletes anything not present. Idempotent by
 * design, mirroring SyncGuildChannelsAction exactly.
 *
 * See openspec specs/discord-roles - "Role sync on guild join", "Role sync
 * stays current"; design.md Decision 2.
 */
class SyncGuildRolesAction
{
    /**
     * @param  list<array{discord_role_id: string, name: string}>  $roles
     */
    public function execute(Guild $guild, array $roles): void
    {
        $seenDiscordRoleIds = [];

        foreach ($roles as $role) {
            DiscordRole::query()->updateOrCreate(
                ['guild_id' => $guild->id, 'discord_role_id' => $role['discord_role_id']],
                ['name' => $role['name']],
            );

            $seenDiscordRoleIds[] = $role['discord_role_id'];
        }

        DiscordRole::query()
            ->where('guild_id', $guild->id)
            ->whereNotIn('discord_role_id', $seenDiscordRoleIds)
            ->delete();
    }
}
