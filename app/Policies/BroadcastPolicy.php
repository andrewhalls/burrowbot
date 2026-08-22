<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Broadcast;
use App\Models\User;
use App\Support\GuildAdmins\GuildAdminSection;

class BroadcastPolicy
{
    public function view(User $user, Broadcast $broadcast): bool
    {
        return $user->hasGuildAdminSection($broadcast->guild_id, GuildAdminSection::BROADCASTS);
    }

    public function manage(User $user, Broadcast $broadcast): bool
    {
        return $user->hasGuildAdminSection($broadcast->guild_id, GuildAdminSection::BROADCASTS);
    }
}
