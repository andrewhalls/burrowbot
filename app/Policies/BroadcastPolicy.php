<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Broadcast;
use App\Models\User;

class BroadcastPolicy
{
    public function view(User $user, Broadcast $broadcast): bool
    {
        return $user->isAdminOfGuild($broadcast->guild_id);
    }

    public function manage(User $user, Broadcast $broadcast): bool
    {
        return $user->isAdminOfGuild($broadcast->guild_id);
    }
}
