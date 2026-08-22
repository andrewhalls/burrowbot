<?php

declare(strict_types=1);

namespace App\Support\GuildAdmins;

/**
 * The fixed set of grantable dashboard sections a scoped (`granted`) guild
 * admin can be given access to - one key per entry in the dashboard
 * sidebar's `$links` array, reused as the single source of truth by the
 * sidebar, the Admins screen's section checkboxes, and every guild-scoped
 * Policy.
 *
 * See openspec specs/guild-admin-permissions - design.md Decision 1.
 */
class GuildAdminSection
{
    public const SETTINGS = 'settings';

    public const THEMES = 'themes';

    public const EVENT_ROLE_SETS = 'event-role-sets';

    public const EVENTS = 'events';

    public const GIVEAWAYS = 'giveaways';

    public const STANDARD_GIVEAWAYS = 'standard-giveaways';

    public const BROADCASTS = 'broadcasts';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::SETTINGS,
            self::THEMES,
            self::EVENT_ROLE_SETS,
            self::EVENTS,
            self::GIVEAWAYS,
            self::STANDARD_GIVEAWAYS,
            self::BROADCASTS,
        ];
    }

    /**
     * @return array<string, string> section key => display label
     */
    public static function labels(): array
    {
        return [
            self::SETTINGS => 'Settings',
            self::THEMES => 'Collection themes',
            self::EVENT_ROLE_SETS => 'Event role sets',
            self::EVENTS => 'Events',
            self::GIVEAWAYS => 'Popup giveaways',
            self::STANDARD_GIVEAWAYS => 'Standard giveaways',
            self::BROADCASTS => 'Broadcasts',
        ];
    }
}
