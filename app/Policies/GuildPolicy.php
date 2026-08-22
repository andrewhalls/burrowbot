<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;

/**
 * Every dashboard action scoped to a guild (viewing/managing its themes,
 * giveaways, members, entries) goes through `view`/`manage` here so
 * cross-guild access is denied in exactly one place.
 *
 * See openspec specs/auth - "Per-guild admin authorization".
 */
class GuildPolicy
{
    public function view(User $user, Guild $guild): bool
    {
        return $user->isAdminOfGuild($guild);
    }

    public function manage(User $user, Guild $guild): bool
    {
        return $user->isAdminOfGuild($guild);
    }

    /**
     * Whether this user can invite/edit/revoke other admins of the guild -
     * restricted to full (Discord-synced) admins, regardless of which
     * sections a scoped admin holds, so scoped access can never be used to
     * bootstrap broader access for oneself or others.
     *
     * See openspec specs/guild-admin-permissions - "Admin management
     * restricted to full admins"; design.md Decision 4.
     */
    public function manageAdmins(User $user, Guild $guild): bool
    {
        return $user->guildAdmins()
            ->where('guild_id', $guild->id)
            ->where('source', GuildAdmin::SOURCE_DISCORD_SYNC)
            ->exists();
    }
}
