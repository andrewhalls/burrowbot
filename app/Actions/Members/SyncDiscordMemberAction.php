<?php

declare(strict_types=1);

namespace App\Actions\Members;

use App\Models\DiscordMember;
use App\Models\Guild;

/**
 * Upserts a guild-scoped member record. Called both from the internal
 * PUT /internal/guilds/{guild}/members/{discordUserId} endpoint and
 * opportunistically from the giveaway-entry flow, so the member directory
 * stays current without a separate full-guild sync.
 *
 * See openspec specs/member-directory - "Member record sync".
 */
class SyncDiscordMemberAction
{
    public function execute(Guild $guild, string $discordUserId, string $username, ?string $avatarUrl = null, ?string $displayName = null): DiscordMember
    {
        return DiscordMember::query()->updateOrCreate(
            ['guild_id' => $guild->id, 'discord_user_id' => $discordUserId],
            ['username' => $username, 'avatar_url' => $avatarUrl, 'display_name' => $displayName],
        );
    }
}
