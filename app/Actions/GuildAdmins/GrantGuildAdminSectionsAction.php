<?php

declare(strict_types=1);

namespace App\Actions\GuildAdmins;

use App\Models\DiscordMember;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;

/**
 * Grants a Discord guild member section-scoped admin access, keyed by
 * their Discord user ID rather than a Burrow user record - they may never
 * have logged into Burrow yet. `user_id` is resolved immediately if they
 * already have one; otherwise it's backfilled the first time they log in
 * (see SyncGuildAdminsForUserAction).
 *
 * Re-inviting an already-granted member replaces their section list rather
 * than creating a second row (design.md Decision 2, add-guild-admin-permissions).
 *
 * See openspec specs/guild-admin-permissions - "Granting a section-scoped admin".
 */
class GrantGuildAdminSectionsAction
{
    /**
     * @param  list<string>  $sections
     */
    public function execute(Guild $guild, DiscordMember $member, array $sections): GuildAdmin
    {
        $user = User::query()->where('discord_user_id', $member->discord_user_id)->first();

        return GuildAdmin::query()->updateOrCreate(
            ['guild_id' => $guild->id, 'discord_user_id' => $member->discord_user_id],
            [
                'user_id' => $user?->id,
                'source' => GuildAdmin::SOURCE_GRANTED,
                'sections' => array_values($sections),
            ],
        );
    }
}
