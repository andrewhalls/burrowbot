<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Models\EventAttendance;
use App\Models\EventOccurrence;
use App\Models\EventRoleSignup;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

/**
 * Staff screen for one event occurrence: roster grouped by role (with a
 * waitlisted sub-section per role) plus a Not Attending list, searchable
 * by member. Mirrors the giveaway platform's GiveawayDashboard.
 */
class OccurrenceRoster extends Component
{
    public EventOccurrence $occurrence;

    public string $search = '';

    public function mount(EventOccurrence $occurrence): void
    {
        $this->authorize('view', $occurrence->event);

        $this->occurrence = $occurrence;
    }

    public function render(): View
    {
        $signups = EventRoleSignup::query()
            ->where('event_occurrence_id', $this->occurrence->id)
            ->with('discordMember')
            ->when($this->search !== '', function (Builder $query) {
                $query->whereHas('discordMember', function (Builder $memberQuery) {
                    $memberQuery->search($this->search);
                });
            })
            ->get();

        $roles = $this->occurrence->eventRoleSet->roles->map(function ($role) use ($signups) {
            $roleSignups = $signups->where('event_role_id', $role->id);

            return [
                'role' => $role,
                'confirmed' => $roleSignups->where('is_waitlisted', false)->values(),
                'waitlisted' => $roleSignups->where('is_waitlisted', true)->values(),
            ];
        });

        $notAttending = EventAttendance::query()
            ->where('event_occurrence_id', $this->occurrence->id)
            ->where('status', EventAttendance::STATUS_NOT_ATTENDING)
            ->with('discordMember')
            ->when($this->search !== '', function (Builder $query) {
                $query->whereHas('discordMember', function (Builder $memberQuery) {
                    $memberQuery->search($this->search);
                });
            })
            ->get();

        return view('livewire.events.occurrence-roster', [
            'roles' => $roles,
            'notAttending' => $notAttending,
        ]);
    }
}
