<?php

declare(strict_types=1);

namespace App\Actions\Giveaways;

use App\Models\GiveawayEntry;
use App\Models\User;

/**
 * Marks an entry as fulfilled (handed out), recording who and when.
 *
 * See openspec specs/giveaway-admin-dashboard - "Mark an entry fulfilled".
 */
class FulfillGiveawayEntryAction
{
    public function execute(GiveawayEntry $entry, User $staff): GiveawayEntry
    {
        $entry->update([
            'fulfilled_at' => now(),
            'fulfilled_by_user_id' => $staff->id,
        ]);

        return $entry;
    }
}
