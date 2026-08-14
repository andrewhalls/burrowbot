<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Actions\Members\SyncDiscordMemberAction;
use App\Models\EventAttendance;
use App\Models\EventOccurrence;
use App\Models\EventRole;
use App\Models\EventRoleSignup;
use App\Support\Events\SignupResult;
use Illuminate\Support\Facades\DB;

/**
 * Processes a member selecting a role on an event occurrence: the single
 * place that enforces the start-time cutoff, role capacity/waitlist, and
 * the role set's single-vs-multiple-role policy.
 *
 * See openspec specs/event-signups (all requirements).
 */
class SignUpForEventRoleAction
{
    public function __construct(
        private readonly SyncDiscordMemberAction $syncMember,
        private readonly PromoteFromWaitlistAction $promoteFromWaitlist,
    ) {}

    public function execute(EventOccurrence $occurrence, EventRole $role, string $discordUserId, string $discordUsername, ?string $discordDisplayName = null): SignupResult
    {
        return DB::transaction(function () use ($occurrence, $role, $discordUserId, $discordUsername, $discordDisplayName) {
            // Locks the occurrence row so concurrent signups on the SAME
            // occurrence serialize here - mirrors JoinGiveawayAction's
            // concurrency-safety approach (giveaway design.md §3).
            $locked = EventOccurrence::query()->lockForUpdate()->findOrFail($occurrence->id);

            $member = $this->syncMember->execute($locked->event->guild, $discordUserId, $discordUsername, displayName: $discordDisplayName);

            // Authoritative cutoff: independent of `status`.
            if ($locked->hasStarted()) {
                return SignupResult::rejected('This event has already started.');
            }

            if ($role->event_role_set_id !== $locked->event_role_set_id) {
                return SignupResult::rejected('That role is not available for this occurrence.');
            }

            $existing = EventRoleSignup::query()
                ->where('event_occurrence_id', $locked->id)
                ->where('discord_member_id', $member->id)
                ->where('event_role_id', $role->id)
                ->first();

            if ($existing) {
                return $existing->is_waitlisted ? SignupResult::waitlisted($role) : SignupResult::confirmed($role);
            }

            // Determine the outcome for the requested role BEFORE mutating
            // anything else, so a capacity rejection leaves the member's
            // existing attendance/role signup entirely untouched.
            $isWaitlisted = false;

            if (! $role->isUncapped()) {
                $confirmedCount = EventRoleSignup::query()
                    ->where('event_occurrence_id', $locked->id)
                    ->where('event_role_id', $role->id)
                    ->where('is_waitlisted', false)
                    ->count();

                if ($confirmedCount >= $role->capacity) {
                    if (! $role->isWaitlisted()) {
                        return SignupResult::rejected('That role is full.');
                    }

                    $isWaitlisted = true;
                }
            }

            $roleSet = $locked->eventRoleSet;

            if (! $roleSet->allow_multiple_roles) {
                $previous = EventRoleSignup::query()
                    ->where('event_occurrence_id', $locked->id)
                    ->where('discord_member_id', $member->id)
                    ->first();

                if ($previous) {
                    $previousRole = $previous->eventRole;
                    $wasConfirmed = ! $previous->is_waitlisted;
                    $previous->delete();

                    if ($wasConfirmed) {
                        $this->promoteFromWaitlist->execute($locked, $previousRole);
                    }
                }
            }

            EventAttendance::query()->updateOrCreate(
                ['event_occurrence_id' => $locked->id, 'discord_member_id' => $member->id],
                ['status' => EventAttendance::STATUS_ATTENDING],
            );

            EventRoleSignup::query()->create([
                'event_occurrence_id' => $locked->id,
                'discord_member_id' => $member->id,
                'event_role_id' => $role->id,
                'is_waitlisted' => $isWaitlisted,
            ]);

            return $isWaitlisted ? SignupResult::waitlisted($role) : SignupResult::confirmed($role);
        });
    }
}
