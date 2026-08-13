<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\StandardGiveaways\CloseAndDrawStandardGiveawayOccurrenceAction;
use App\Models\StandardGiveawayOccurrence;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Closes every `posted` standard giveaway occurrence whose `ends_at` has
 * passed and draws its winners. This is housekeeping/orchestration only -
 * the authoritative entry cutoff lives in the entry Action's own
 * `now() >= ends_at` check, not here (mirrors giveaways:close-expired and
 * events:post-due-occurrences).
 *
 * See openspec specs/standard-giveaway-occurrences - "Automatic closing
 * and drawing at end time".
 */
#[Signature('standard-giveaways:close-expired')]
#[Description('Close expired standard giveaway occurrences and draw their winners.')]
class CloseExpiredStandardGiveawayOccurrences extends Command
{
    public function handle(CloseAndDrawStandardGiveawayOccurrenceAction $closeAndDraw): int
    {
        $occurrences = StandardGiveawayOccurrence::query()
            ->where('status', StandardGiveawayOccurrence::STATUS_POSTED)
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($occurrences as $occurrence) {
            $closeAndDraw->execute($occurrence);
        }

        $this->info("Closed {$occurrences->count()} expired occurrence(s).");

        return self::SUCCESS;
    }
}
