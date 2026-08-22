<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EventRoleSet;
use App\Models\User;
use App\Support\GuildAdmins\GuildAdminSection;

class EventRoleSetPolicy
{
    public function view(User $user, EventRoleSet $roleSet): bool
    {
        return $user->hasGuildAdminSection($roleSet->guild_id, GuildAdminSection::EVENT_ROLE_SETS);
    }

    public function manage(User $user, EventRoleSet $roleSet): bool
    {
        return $user->hasGuildAdminSection($roleSet->guild_id, GuildAdminSection::EVENT_ROLE_SETS);
    }
}
