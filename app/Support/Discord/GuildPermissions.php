<?php

declare(strict_types=1);

namespace App\Support\Discord;

/**
 * Decodes the Discord permissions bitfield returned by
 * GET /users/@me/guilds to answer "can this user administer this guild".
 *
 * @see https://discord.com/developers/docs/topics/permissions
 */
final class GuildPermissions
{
    private const ADMINISTRATOR = 0x8;

    private const MANAGE_GUILD = 0x20;

    public static function grantsGuildAdmin(int|string $permissions): bool
    {
        $bits = (int) $permissions;

        return ($bits & self::ADMINISTRATOR) === self::ADMINISTRATOR
            || ($bits & self::MANAGE_GUILD) === self::MANAGE_GUILD;
    }
}
