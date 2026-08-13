<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function view(User $user, Event $event): bool
    {
        return $user->isAdminOfGuild($event->guild_id);
    }

    public function manage(User $user, Event $event): bool
    {
        return $user->isAdminOfGuild($event->guild_id);
    }
}
