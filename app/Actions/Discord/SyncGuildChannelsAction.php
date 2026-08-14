<?php

declare(strict_types=1);

namespace App\Actions\Discord;

use App\Models\DiscordChannel;
use App\Models\Guild;

/**
 * Replaces a guild's entire synced channel list with exactly what's given -
 * upserts everything present, deletes anything not present. Idempotent by
 * design so it's safe to call from a full-guild sync, a single-channel
 * gateway event (recomputing and resending the full list), or a periodic
 * fallback timer without any of them needing to coordinate.
 *
 * See openspec specs/discord-channels - "Channel sync on guild join",
 * "Channel sync stays current"; design.md Decision 1.
 */
class SyncGuildChannelsAction
{
    /**
     * @param  list<array{discord_channel_id: string, name: string}>  $channels
     */
    public function execute(Guild $guild, array $channels): void
    {
        $seenDiscordChannelIds = [];

        foreach ($channels as $channel) {
            DiscordChannel::query()->updateOrCreate(
                ['guild_id' => $guild->id, 'discord_channel_id' => $channel['discord_channel_id']],
                ['name' => $channel['name']],
            );

            $seenDiscordChannelIds[] = $channel['discord_channel_id'];
        }

        DiscordChannel::query()
            ->where('guild_id', $guild->id)
            ->whereNotIn('discord_channel_id', $seenDiscordChannelIds)
            ->delete();
    }
}
