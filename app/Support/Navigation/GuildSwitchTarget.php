<?php

declare(strict_types=1);

namespace App\Support\Navigation;

use App\Models\Guild;
use Illuminate\Routing\Route;

/**
 * Computes where the guild-switcher dropdown should navigate to when the
 * user picks a different guild while on a guild-scoped page.
 *
 * See design.md (improve-dashboard-shell) Decision 3: a route qualifies for
 * "same page type, new guild" only when `{guild}` is its sole declared URI
 * parameter - anything needing more (an occurrence ID, a giveaway ID, ...)
 * doesn't exist for an arbitrary other guild, so it falls back to that
 * guild's settings page. This needs no maintenance as new guild-scoped
 * routes are added: a route only has to keep `{guild}` as its only
 * parameter to get "switch and land on the same page" for free.
 */
final class GuildSwitchTarget
{
    public static function resolve(?Route $currentRoute, Guild $targetGuild): string
    {
        $routeName = $currentRoute?->getName();

        $qualifies = $routeName !== null
            && str_starts_with($routeName, 'guilds.')
            && $currentRoute->parameterNames() === ['guild'];

        return $qualifies
            ? route($routeName, ['guild' => $targetGuild])
            : route('guilds.settings', ['guild' => $targetGuild]);
    }
}
