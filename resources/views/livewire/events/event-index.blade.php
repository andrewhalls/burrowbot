<div class="space-y-6">
    <div class="flex items-center justify-between gap-2">
        <h2 class="text-lg font-semibold">Events</h2>
        <div class="flex items-center gap-2">
            @error('delete') <p class="text-xs text-danger">{{ $message }}</p> @enderror
            @if ($selectedEvent && ! $selectedOccurrence && ! $showCreateForm)
                <button type="button" wire:click="toggleEdit"
                        class="rounded-pill bg-surface-hover hover:bg-line px-4 py-2 text-sm font-medium text-ink">
                    {{ $editing ? 'Cancel' : 'Edit' }}
                </button>
                @if ($selectedEvent->isDeletable())
                    <button type="button" wire:click="delete" wire:confirm="Delete this event? This cannot be undone."
                            class="rounded-pill border border-line text-danger hover:bg-danger/10 px-4 py-2 text-sm font-medium">
                        Delete
                    </button>
                @endif
            @endif
            <button type="button" wire:click="toggleCreateForm" class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
                {{ $showCreateForm ? 'Cancel' : '+ New event' }}
            </button>
        </div>
    </div>

    <x-list-detail-shell :selected="$selectedEvent !== null || $showCreateForm">
        <x-slot:list>
            <label class="flex items-center gap-2 text-xs text-muted mb-3">
                <input type="checkbox" wire:model.live="showArchived">
                Show archived
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse ($events as $event)
                    <div wire:key="event-tile-{{ $event->id }}" wire:click="select({{ $event->id }})"
                         @class([
                            'rounded-card border p-3 cursor-pointer hover:bg-surface-hover transition-colors flex flex-col',
                            'border-accent' => $selectedEvent?->id === $event->id,
                            'border-line' => $selectedEvent?->id !== $event->id,
                            'opacity-60' => $event->isArchived(),
                         ])>
                        @if ($event->image_url)
                            <img src="{{ $event->image_url }}" alt="" class="w-full h-20 rounded-control object-cover mb-2">
                        @endif
                        <p class="font-medium text-ink text-sm truncate">{{ $event->title }}</p>
                        <div class="flex items-center gap-1 flex-wrap mt-1">
                            <span @class([
                                'rounded-pill px-2 py-0.5 text-[11px] font-medium shrink-0',
                                'bg-success/15 text-success' => $event->status === 'active',
                                'bg-warning/15 text-warning' => $event->status === 'paused',
                                'bg-danger/15 text-danger' => $event->status === 'cancelled',
                            ])>{{ ucfirst($event->status) }}</span>
                            @if ($event->isArchived())
                                <span class="rounded-pill px-2 py-0.5 text-[11px] font-medium shrink-0 bg-surface-hover text-muted">Archived</span>
                            @endif
                        </div>
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
                            @if ($event->isArchived())
                                <button type="button" wire:click.stop="unarchive({{ $event->id }})" class="text-muted hover:text-ink">Unarchive</button>
                            @else
                                <button type="button" wire:click.stop="archive({{ $event->id }})" wire:confirm="Archive this event? This also cancels it." class="text-muted hover:text-ink">Archive</button>
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
            @if ($showCreateForm)
                <livewire:events.create-event :guild="$guild" :key="'create-event-'.$guild->id" />
            @elseif ($selectedOccurrence)
                <div>
                    <button type="button" wire:click="deselectOccurrence" class="mb-3 text-sm text-muted hover:text-ink">&larr; Back to {{ $selectedEvent->title }}</button>
                    <livewire:events.occurrence-roster :occurrence="$selectedOccurrence" :key="'occurrence-roster-'.$selectedOccurrence->id" />
                </div>
            @elseif ($editing && $selectedEvent)
                <livewire:events.edit-event :event="$selectedEvent" :key="'edit-event-'.$selectedEvent->id" />
            @elseif ($selectedEvent)
                @include('livewire.events.partials.event-summary', ['event' => $selectedEvent])
            @endif
        </x-slot:detail>
    </x-list-detail-shell>
</div>
