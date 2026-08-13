<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Giveaway;
use App\Models\User;

class GiveawayPolicy
{
    public function view(User $user, Giveaway $giveaway): bool
    {
        return $user->isAdminOfGuild($giveaway->guild_id);
    }

    public function manage(User $user, Giveaway $giveaway): bool
    {
        return $user->isAdminOfGuild($giveaway->guild_id);
    }
}
