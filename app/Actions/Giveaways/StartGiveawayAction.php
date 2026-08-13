<?php

declare(strict_types=1);

namespace App\Actions\Giveaways;

use App\Jobs\PostGiveawayMessage;
use App\Models\Giveaway;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Starts a draft giveaway: posts it to Discord and transitions it to
 * `active`. Called by both a manual "Start" click and the
 * `giveaways:post-due` scheduled command. Follows the same
 * dispatch-a-queued-job-onto-the-outbound-queue pattern as
 * `CloseExpiredGiveaways`/`CloseGiveawayMessage` - the job itself creates
 * the `DiscordOutboundAction` row when it runs, rather than this action
 * creating it inline.
 *
 * The row lock guards against a manual click and the scheduled command
 * racing on the same giveaway; whichever transaction wins the lock starts
 * it, the other finds it already non-draft and throws - callers that can
 * legitimately race (the scheduled command) must catch this.
 *
 * See openspec specs/giveaway-lifecycle - "Starting a giveaway",
 * "Scheduled start".
 */
class StartGiveawayAction
{
    public function execute(Giveaway $giveaway): Giveaway
    {
        return DB::transaction(function () use ($giveaway) {
            $locked = Giveaway::query()->lockForUpdate()->findOrFail($giveaway->id);

            if (! $locked->isDraft()) {
                throw new InvalidArgumentException('Only a draft giveaway can be started.');
            }

            $startedAt = now();

            $locked->update([
                'status' => Giveaway::STATUS_ACTIVE,
                'starts_at' => $startedAt,
                'ends_at' => $startedAt->clone()->addMinutes($locked->duration_minutes),
            ]);

            PostGiveawayMessage::dispatch($locked)->onQueue(Config::string('discord.outbound_queue'));

            return $locked;
        });
    }
}
