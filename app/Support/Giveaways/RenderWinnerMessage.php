<?php

declare(strict_types=1);

namespace App\Support\Giveaways;

/**
 * Pure function: substitutes the fixed set of placeholders a guild admin's
 * per-winner message template may reference - {winner} and {prize} - with
 * plain str_replace. A template using either, both, or neither is valid;
 * unrecognized tokens are left as literal text. Mirrors
 * App\Support\StandardGiveaways\RenderCongratsMessage's exact behavior,
 * scoped down to a single winner instead of a batch.
 */
final class RenderWinnerMessage
{
    public function __invoke(string $template, string $winnerDiscordUserId, string $prize): string
    {
        $replacements = [
            '{winner}' => "<@{$winnerDiscordUserId}>",
            '{prize}' => $prize,
        ];

        return strtr($template, $replacements);
    }
}
