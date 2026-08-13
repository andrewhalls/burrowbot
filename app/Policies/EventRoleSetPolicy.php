<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EventRoleSet;
use App\Models\User;

class EventRoleSetPolicy
{
    public function view(User $user, EventRoleSet $roleSet): bool
    {
        return $user->isAdminOfGuild($roleSet->guild_id);
    }

    public function manage(User $user, EventRoleSet $roleSet): bool
    {
        return $user->isAdminOfGuild($roleSet->guild_id);
    }
}
