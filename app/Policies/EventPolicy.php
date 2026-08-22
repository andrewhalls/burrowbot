<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use App\Support\GuildAdmins\GuildAdminSection;

class EventPolicy
{
    public function view(User $user, Event $event): bool
    {
        return $user->hasGuildAdminSection($event->guild_id, GuildAdminSection::EVENTS);
    }

    public function manage(User $user, Event $event): bool
    {
        return $user->hasGuildAdminSection($event->guild_id, GuildAdminSection::EVENTS);
    }
}
