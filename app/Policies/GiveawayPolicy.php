<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Giveaway;
use App\Models\User;
use App\Support\GuildAdmins\GuildAdminSection;

class GiveawayPolicy
{
    public function view(User $user, Giveaway $giveaway): bool
    {
        return $user->hasGuildAdminSection($giveaway->guild_id, GuildAdminSection::GIVEAWAYS);
    }

    public function manage(User $user, Giveaway $giveaway): bool
    {
        return $user->hasGuildAdminSection($giveaway->guild_id, GuildAdminSection::GIVEAWAYS);
    }
}
