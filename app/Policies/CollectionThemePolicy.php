<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CollectionTheme;
use App\Models\User;

/**
 * See openspec specs/themed-item-lists ("collection-themes") - theme
 * management is scoped to admins of the theme's own guild.
 */
class CollectionThemePolicy
{
    public function view(User $user, CollectionTheme $theme): bool
    {
        return $user->isAdminOfGuild($theme->guild_id);
    }

    public function manage(User $user, CollectionTheme $theme): bool
    {
        return $user->isAdminOfGuild($theme->guild_id);
    }
}
