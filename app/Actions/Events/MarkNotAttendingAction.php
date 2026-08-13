<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Actions\Members\SyncDiscordMemberAction;
use App\Models\EventAttendance;
use App\Models\EventOccurrence;
use App\Models\EventRoleSignup;
use App\Support\Events\SignupResult;
use Illuminate\Support\Facades\DB;

/**
 * Marks a member Not Attending on an occurrence: clears every role signup
 * (confirmed or waitlisted) they hold and promotes waitlisted members into
 * any capacity that frees up.
 *
 * See openspec specs/event-signups - "Marking Not Attending clears role signups".
 */
class MarkNotAttendingAction
{
    public function __construct(
        private readonly SyncDiscordMemberAction $syncMember,
        private readonly PromoteFromWaitlistAction $promoteFromWaitlist,
    ) {}

    public function execute(EventOccurrence $occurrence, string $discordUserId, string $discordUsername): SignupResult
    {
        return DB::transaction(function () use ($occurrence, $discordUserId, $discordUsername) {
            $locked = EventOccurrence::query()->lockForUpdate()->findOrFail($occurrence->id);

            $member = $this->syncMember->execute($locked->event->guild, $discordUserId, $discordUsername);

            if ($locked->hasStarted()) {
                return SignupResult::rejected('This event has already started.');
            }

            $roleSignups = EventRoleSignup::query()
                ->where('event_occurrence_id', $locked->id)
                ->where('discord_member_id', $member->id)
                ->get();

            foreach ($roleSignups as $signup) {
                $wasConfirmed = ! $signup->is_waitlisted;
                $role = $signup->eventRole;
                $signup->delete();

                if ($wasConfirmed) {
                    $this->promoteFromWaitlist->execute($locked, $role);
                }
            }

            EventAttendance::query()->updateOrCreate(
                ['event_occurrence_id' => $locked->id, 'discord_member_id' => $member->id],
                ['status' => EventAttendance::STATUS_NOT_ATTENDING],
            );

            return SignupResult::notAttending();
        });
    }
}
