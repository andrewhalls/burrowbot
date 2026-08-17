<?php

declare(strict_types=1);

namespace App\Support\StandardGiveaways;

use Illuminate\Support\Carbon;

/**
 * Pure function: substitutes the fixed set of placeholders a guild admin's
 * congratulations message template may reference - {winners}, {prize},
 * {claim_link}, {claim_deadline} - with plain str_replace. A template using
 * any subset (including none) of these tokens is valid; unrecognized tokens
 * are left as literal text (design.md Decision 4).
 */
final class RenderCongratsMessage
{
    /**
     * @param  list<string>  $winnerDiscordUserIds
     */
    public function __invoke(
        string $template,
        array $winnerDiscordUserIds,
        string $prize,
        ?string $claimLink,
        ?Carbon $claimDeadlineAt,
    ): string {
        $replacements = [
            '{winners}' => collect($winnerDiscordUserIds)->map(fn (string $id) => "<@{$id}>")->implode(', '),
            '{prize}' => $prize,
            '{claim_link}' => $claimLink ?? '',
            '{claim_deadline}' => $claimDeadlineAt ? "<t:{$claimDeadlineAt->getTimestamp()}:R>" : '',
        ];

        return strtr($template, $replacements);
    }
}
