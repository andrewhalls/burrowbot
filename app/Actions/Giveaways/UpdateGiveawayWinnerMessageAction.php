<?php

declare(strict_types=1);

namespace App\Actions\Giveaways;

use App\Models\Giveaway;

/**
 * Sets a giveaway's winner-message channel and template. Unlike
 * UpdateGiveawayDraftAction, this has no status check - these fields stay
 * editable at any giveaway status (draft, active, or closed) since they
 * only affect future win events, never the already-posted Discord message.
 *
 * See openspec specs/giveaway-lifecycle - "Winner-message configuration
 * stays editable regardless of giveaway status".
 */
class UpdateGiveawayWinnerMessageAction
{
    public function execute(Giveaway $giveaway, ?string $channelId, ?string $template): Giveaway
    {
        $giveaway->update([
            'winner_message_channel_id' => $channelId,
            'winner_message_template' => $template,
        ]);

        return $giveaway;
    }
}
