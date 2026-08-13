<?php

declare(strict_types=1);

namespace App\Support\Discord;

/**
 * Builds the "invite this bot to your server" OAuth2 URL shown in the
 * zero-guild onboarding state. The permissions constant covers exactly what
 * the bot process uses today (bot/src/discordAdapter.js) and nothing more -
 * no Administrator/Manage Server style permission is ever requested. See
 * design.md's Decision 2 table for the exact breakdown:
 *
 *   View Channel                 1<<10   1,024
 *   Send Messages                1<<11   2,048
 *   Embed Links                  1<<14   16,384
 *   Read Message History         1<<16   65,536
 *   Mention @everyone/roles      1<<17   131,072
 *   Create Public Threads        1<<34   17,179,869,184
 *   Send Messages in Threads     1<<38   274,877,906,944
 *   Total                                292,057,992,192
 *
 * Scope is `bot` only - no `applications.commands`, since the bot registers
 * no slash commands.
 */
final class BotInviteUrl
{
    public const PERMISSIONS = 292057992192;

    public static function build(): string
    {
        $clientId = (string) config('services.discord.client_id');

        return sprintf(
            'https://discord.com/oauth2/authorize?client_id=%s&scope=bot&permissions=%d',
            $clientId,
            self::PERMISSIONS,
        );
    }
}
