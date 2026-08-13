<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\EventOccurrence;
use App\Models\EventRole;
use App\Models\EventRoleSignup;

/**
 * Promotes the earliest-waitlisted member for a role to confirmed status,
 * called whenever a confirmed signup on that role is freed.
 *
 * See openspec specs/event-signups - "Waitlist promotion on capacity release".
 */
class PromoteFromWaitlistAction
{
    public function execute(EventOccurrence $occurrence, EventRole $role): void
    {
        EventRoleSignup::query()
            ->where('event_occurrence_id', $occurrence->id)
            ->where('event_role_id', $role->id)
            ->where('is_waitlisted', true)
            ->orderBy('created_at')
            ->first()
            ?->update(['is_waitlisted' => false]);
    }
}
