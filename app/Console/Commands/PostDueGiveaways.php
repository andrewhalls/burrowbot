<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Giveaways\StartGiveawayAction;
use App\Models\Giveaway;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Starts every `draft` giveaway whose `scheduled_start_at` has passed -
 * the automatic half of `giveaway-lifecycle` - "Scheduled start". Explicit
 * `scheduled_start_at <= now()` filter (design.md Decision 2) rather than
 * a blind sweep of every pending row.
 *
 * `StartGiveawayAction` throws if a giveaway is no longer draft by the
 * time its lock is acquired (e.g. a manual start won the race in between
 * the query above and this loop reaching it) - caught per-giveaway so one
 * race doesn't abort the rest of the batch.
 */
#[Signature('giveaways:post-due')]
#[Description('Start every draft giveaway whose scheduled start time has passed.')]
class PostDueGiveaways extends Command
{
    public function handle(StartGiveawayAction $startGiveaway): int
    {
        $due = Giveaway::query()
            ->where('status', Giveaway::STATUS_DRAFT)
            ->whereNotNull('scheduled_start_at')
            ->where('scheduled_start_at', '<=', now())
            ->get();

        $started = 0;

        foreach ($due as $giveaway) {
            try {
                $startGiveaway->execute($giveaway);
                $started++;
            } catch (InvalidArgumentException) {
                // Already started by something else between the query above
                // and this loop reaching it - not this run's job to report.
            }
        }

        $this->info("Started {$started} scheduled giveaway(s).");

        return self::SUCCESS;
    }
}
