<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Actions\Events\ArchiveEventAction;
use App\Actions\Events\DeleteEventAction;
use App\Actions\Events\UnarchiveEventAction;
use App\Actions\Events\UpdateEventStatusAction;
use App\Models\Event;
use App\Models\EventOccurrence;
use App\Models\Guild;
use App\Support\GuildAdmins\GuildAdminSection;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Component;

class EventIndex extends Component
{
    public Guild $guild;

    public bool $showCreateForm = false;

    public ?int $selectedId = null;

    /**
     * Which occurrence's roster to show inside the currently-selected
     * event's detail panel - nested one level deeper than $selectedId,
     * cleared whenever the event selection itself changes.
     */
    public ?int $selectedOccurrenceId = null;

    public bool $editing = false;

    public bool $showArchived = false;

    public function mount(Guild $guild): void
    {
        abort_unless(Auth::user()->hasGuildAdminSection($guild, GuildAdminSection::EVENTS), 403);

        $this->guild = $guild;
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = ! $this->showCreateForm;

        if ($this->showCreateForm) {
            $this->selectedId = null;
            $this->selectedOccurrenceId = null;
            $this->editing = false;
        }
    }

    #[On('event-created')]
    public function closeCreateForm(int $eventId): void
    {
        $this->showCreateForm = false;
        $this->selectedId = $eventId;
    }

    #[On('event-updated')]
    public function closeEditForm(): void
    {
        $this->editing = false;
    }

    public function setStatus(int $eventId, string $status, UpdateEventStatusAction $updateStatus): void
    {
        $event = Event::query()->where('guild_id', $this->guild->id)->findOrFail($eventId);

        $this->authorize('manage', $event);

        $updateStatus->execute($event, $status);
    }

    public function archive(int $eventId, ArchiveEventAction $archiveEvent): void
    {
        $event = Event::query()->where('guild_id', $this->guild->id)->findOrFail($eventId);

        $this->authorize('manage', $event);

        $archiveEvent->execute($event);
    }

    public function unarchive(int $eventId, UnarchiveEventAction $unarchiveEvent): void
    {
        $event = Event::query()->where('guild_id', $this->guild->id)->findOrFail($eventId);

        $this->authorize('manage', $event);

        $unarchiveEvent->execute($event);
    }

    public function select(int $eventId): void
    {
        $exists = Event::query()->where('guild_id', $this->guild->id)->where('id', $eventId)->exists();

        $this->selectedId = $exists ? $eventId : null;
        $this->selectedOccurrenceId = null;
        $this->editing = false;
        $this->showCreateForm = false;
    }

    public function deselect(): void
    {
        $this->selectedId = null;
        $this->selectedOccurrenceId = null;
        $this->editing = false;
    }

    public function toggleEdit(): void
    {
        $event = Event::query()->where('guild_id', $this->guild->id)->findOrFail($this->selectedId);

        $this->authorize('manage', $event);

        $this->editing = ! $this->editing;
    }

    public function delete(DeleteEventAction $deleteEvent): void
    {
        $event = Event::query()->where('guild_id', $this->guild->id)->findOrFail($this->selectedId);

        $this->authorize('manage', $event);

        try {
            $deleteEvent->execute($event);
        } catch (InvalidArgumentException $e) {
            $this->addError('delete', $e->getMessage());

            return;
        }

        $this->selectedId = null;
        $this->selectedOccurrenceId = null;
        $this->editing = false;
    }

    public function selectOccurrence(int $occurrenceId): void
    {
        $exists = EventOccurrence::query()->where('event_id', $this->selectedId)->where('id', $occurrenceId)->exists();

        $this->selectedOccurrenceId = $exists ? $occurrenceId : null;
    }

    public function deselectOccurrence(): void
    {
        $this->selectedOccurrenceId = null;
    }

    public function render(): View
    {
        $events = $this->guild->events()
            ->with(['eventRoleSet', 'creator'])
            ->when(! $this->showArchived, fn ($query) => $query->whereNull('archived_at'))
            ->orderByDesc('created_at')
            ->get();

        $selectedEvent = $this->selectedId
            ? Event::query()
                ->with(['eventRoleSet', 'creator', 'occurrences' => fn ($query) => $query->orderByDesc('scheduled_start_at')->limit(5)])
                ->find($this->selectedId)
            : null;

        $selectedOccurrence = $this->selectedOccurrenceId
            ? EventOccurrence::query()->with('event')->find($this->selectedOccurrenceId)
            : null;

        return view('livewire.events.event-index', [
            'events' => $events,
            'selectedEvent' => $selectedEvent,
            'selectedOccurrence' => $selectedOccurrence,
        ]);
    }
}
