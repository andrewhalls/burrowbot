<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\CollectionTheme;
use App\Models\User;
use App\Support\GuildAdmins\GuildAdminSection;

/**
 * See openspec specs/themed-item-lists ("collection-themes") - theme
 * management is scoped to admins of the theme's own guild who hold the
 * "themes" section (design.md Decision 6, add-guild-admin-permissions).
 */
class CollectionThemePolicy
{
    public function view(User $user, CollectionTheme $theme): bool
    {
        return $user->hasGuildAdminSection($theme->guild_id, GuildAdminSection::THEMES);
    }

    public function manage(User $user, CollectionTheme $theme): bool
    {
        return $user->hasGuildAdminSection($theme->guild_id, GuildAdminSection::THEMES);
    }
}
