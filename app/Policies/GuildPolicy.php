<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Guild;
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
}
