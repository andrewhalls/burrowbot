<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StandardGiveaway;
use App\Models\User;
use App\Support\GuildAdmins\GuildAdminSection;

class StandardGiveawayPolicy
{
    public function view(User $user, StandardGiveaway $giveaway): bool
    {
        return $user->hasGuildAdminSection($giveaway->guild_id, GuildAdminSection::STANDARD_GIVEAWAYS);
    }

    public function manage(User $user, StandardGiveaway $giveaway): bool
    {
        return $user->hasGuildAdminSection($giveaway->guild_id, GuildAdminSection::STANDARD_GIVEAWAYS);
    }
}
