<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StandardGiveaway;
use App\Models\User;

class StandardGiveawayPolicy
{
    public function view(User $user, StandardGiveaway $giveaway): bool
    {
        return $user->isAdminOfGuild($giveaway->guild_id);
    }

    public function manage(User $user, StandardGiveaway $giveaway): bool
    {
        return $user->isAdminOfGuild($giveaway->guild_id);
    }
}
