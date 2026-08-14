<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Actions\Events\UpdateEventStatusAction;
use App\Models\Event;
use App\Models\Guild;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class EventIndex extends Component
{
    public Guild $guild;

    public bool $showCreateForm = false;

    public ?int $selectedId = null;

    public function mount(Guild $guild): void
    {
        $this->authorize('view', $guild);

        $this->guild = $guild;
    }

    #[On('event-created')]
    public function closeCreateForm(): void
    {
        $this->showCreateForm = false;
    }

    public function setStatus(int $eventId, string $status, UpdateEventStatusAction $updateStatus): void
    {
        $event = Event::query()->where('guild_id', $this->guild->id)->findOrFail($eventId);

        $this->authorize('manage', $event);

        $updateStatus->execute($event, $status);
    }

    public function select(int $eventId): void
    {
        $exists = Event::query()->where('guild_id', $this->guild->id)->where('id', $eventId)->exists();

        $this->selectedId = $exists ? $eventId : null;
    }

    public function deselect(): void
    {
        $this->selectedId = null;
    }

    public function render(): View
    {
        $events = $this->guild->events()
            ->with('eventRoleSet')
            ->orderByDesc('created_at')
            ->get();

        $selectedEvent = $this->selectedId
            ? Event::query()
                ->with(['eventRoleSet', 'occurrences' => fn ($query) => $query->orderByDesc('scheduled_start_at')->limit(5)])
                ->find($this->selectedId)
            : null;

        return view('livewire.events.event-index', [
            'events' => $events,
            'selectedEvent' => $selectedEvent,
        ]);
    }
}
