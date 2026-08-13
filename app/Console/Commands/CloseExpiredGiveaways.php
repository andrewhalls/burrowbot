<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CloseGiveawayMessage;
use App\Models\Giveaway;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

/**
 * Closes every `active` giveaway whose `ends_at` has passed and requests
 * the bot edit its Discord message. This is housekeeping only - see
 * openspec design.md §4: the authoritative expiry guarantee lives in the
 * join Action's own `now() >= ends_at` check, not here.
 */
#[Signature('giveaways:close-expired')]
#[Description('Close active giveaways whose duration has elapsed and request the bot update their Discord message.')]
class CloseExpiredGiveaways extends Command
{
    public function handle(): int
    {
        $expired = Giveaway::query()
            ->where('status', Giveaway::STATUS_ACTIVE)
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($expired as $giveaway) {
            $giveaway->update(['status' => Giveaway::STATUS_CLOSED]);

            CloseGiveawayMessage::dispatch($giveaway)->onQueue(Config::string('discord.outbound_queue'));
        }

        $this->info("Closed {$expired->count()} expired giveaway(s).");

        return self::SUCCESS;
    }
}
