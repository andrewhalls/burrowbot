<?php

declare(strict_types=1);

namespace App\Actions\Guilds;

use App\Models\Guild;

/**
 * Keeps a guild record in sync with what the bot observes on Discord:
 * created the moment the bot joins, and updated (renamed / marked
 * inactive) as the bot reports further guild lifecycle events.
 *
 * See openspec specs/guild-management - "Guild registration on bot install".
 */
class SyncGuildAction
{
    public function joined(string $discordGuildId, string $name): Guild
    {
        return Guild::query()->updateOrCreate(
            ['discord_guild_id' => $discordGuildId],
            ['name' => $name, 'is_active' => true],
        );
    }

    public function update(Guild $guild, array $attributes): Guild
    {
        $guild->fill($attributes)->save();

        return $guild;
    }
}
