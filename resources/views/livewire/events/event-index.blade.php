<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Events</h2>
        <button type="button" wire:click="$toggle('showCreateForm')" class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
            {{ $showCreateForm ? 'Cancel' : '+ New event' }}
        </button>
    </div>

    @if ($showCreateForm)
        <livewire:events.create-event :guild="$guild" :key="'create-event-'.$guild->id" />
    @endif

    <x-list-detail-shell :selected="$selectedEvent !== null">
        <x-slot:list>
            <div class="grid grid-cols-2 gap-3">
                @forelse ($events as $event)
                    <div wire:key="event-tile-{{ $event->id }}" wire:click="select({{ $event->id }})"
                         @class([
                            'rounded-card border p-3 cursor-pointer hover:bg-surface-hover transition-colors flex flex-col',
                            'border-accent' => $selectedEvent?->id === $event->id,
                            'border-line' => $selectedEvent?->id !== $event->id,
                         ])>
                        <p class="font-medium text-ink text-sm truncate">{{ $event->title }}</p>
                        <span @class([
                            'rounded-pill px-2 py-0.5 text-[11px] font-medium shrink-0 self-start mt-1',
                            'bg-success/15 text-success' => $event->status === 'active',
                            'bg-warning/15 text-warning' => $event->status === 'paused',
                            'bg-danger/15 text-danger' => $event->status === 'cancelled',
                        ])>{{ ucfirst($event->status) }}</span>
                        <p class="text-xs text-muted mt-2">
                            {{ $event->eventRoleSet->name }} &middot;
                            {{ $event->isRecurring() ? 'Recurring' : 'One-off' }}
                        </p>
                        @if ($event->creator)
                            <p class="text-xs text-muted mt-1 truncate">Created by {{ $event->creator->name }}</p>
                        @endif
                        <div class="mt-auto pt-2 flex gap-2 text-xs flex-wrap">
                            @if ($event->status !== 'active')
                                <button type="button" wire:click.stop="setStatus({{ $event->id }}, 'active')" class="text-success hover:text-success">Activate</button>
                            @endif
                            @if ($event->status !== 'paused')
                                <button type="button" wire:click.stop="setStatus({{ $event->id }}, 'paused')" class="text-warning hover:text-warning">Pause</button>
                            @endif
                            @if ($event->status !== 'cancelled')
                                <button type="button" wire:click.stop="setStatus({{ $event->id }}, 'cancelled')" wire:confirm="Cancel this event?" class="text-danger hover:text-danger">Cancel</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-2">
                        <x-list-detail-empty message="No events yet." />
                    </div>
                @endforelse
            </div>
        </x-slot:list>

        <x-slot:detail>
            @if ($selectedOccurrence)
                <div>
                    <button type="button" wire:click="deselectOccurrence" class="mb-3 text-sm text-muted hover:text-ink">&larr; Back to {{ $selectedEvent->title }}</button>
                    <livewire:events.occurrence-roster :occurrence="$selectedOccurrence" :key="'occurrence-roster-'.$selectedOccurrence->id" />
                </div>
            @elseif ($selectedEvent)
                <div class="flex justify-end mb-3">
                    <button type="button" wire:click="toggleEdit"
                            class="rounded-pill bg-surface-hover hover:bg-line px-4 py-2 text-sm font-medium text-ink">
                        {{ $editing ? 'Cancel' : 'Edit' }}
                    </button>
                </div>

                @if ($editing)
                    <livewire:events.edit-event :event="$selectedEvent" :key="'edit-event-'.$selectedEvent->id" />
                @else
                    @include('livewire.events.partials.event-summary', ['event' => $selectedEvent])
                @endif
            @endif
        </x-slot:detail>
    </x-list-detail-shell>
</div>
