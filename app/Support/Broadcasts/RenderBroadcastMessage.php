<?php

declare(strict_types=1);

namespace App\Support\Broadcasts;

use Carbon\CarbonInterface;

/**
 * Resolves a broadcast occurrence's message template's mail-merge
 * placeholders into the final text posted to Discord. Pure/side-effect
 * free - callers pass in already-resolved values rather than this class
 * reaching out to the guild/occurrence itself, so it stays trivially unit
 * testable (design.md Decision 1).
 *
 * Only the fixed placeholder set below is substituted; any other
 * `{{...}}` token in the template is left untouched, matching openspec
 * specs/broadcast-occurrences - "Unrecognized placeholder left as literal
 * text".
 *
 * See openspec specs/broadcast-occurrences - "Message template
 * placeholders".
 */
class RenderBroadcastMessage
{
    public function __invoke(
        string $template,
        string $guildName,
        string $channelId,
        CarbonInterface $postedAt,
        string $timezone,
        ?CarbonInterface $nextOccurrenceDate,
    ): string {
        $localPostedAt = $postedAt->clone()->setTimezone($timezone);

        $replacements = [
            '{{guild_name}}' => $guildName,
            '{{channel}}' => "<#{$channelId}>",
            '{{date}}' => $localPostedAt->format('M j, Y'),
            '{{time}}' => $localPostedAt->format('g:ia'),
            '{{next_occurrence_date}}' => $nextOccurrenceDate?->clone()->setTimezone($timezone)->format('M j, Y') ?? '',
        ];

        return strtr($template, $replacements);
    }
}
