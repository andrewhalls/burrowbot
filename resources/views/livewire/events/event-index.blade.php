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
            <div class="space-y-3">
                @forelse ($events as $event)
                    <div wire:key="event-tile-{{ $event->id }}" wire:click="select({{ $event->id }})"
                         @class([
                            'rounded-card border p-4 cursor-pointer hover:bg-surface-hover transition-colors',
                            'border-accent' => $selectedEvent?->id === $event->id,
                            'border-line' => $selectedEvent?->id !== $event->id,
                         ])>
                        <div class="flex items-center gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-ink truncate">{{ $event->title }}</p>
                                <p class="text-xs text-muted">
                                    {{ $event->eventRoleSet->name }} &middot;
                                    {{ $event->isRecurring() ? 'Recurring' : 'One-off' }}
                                </p>
                            </div>
                            <span @class([
                                'rounded-pill px-2.5 py-1 text-xs font-medium shrink-0',
                                'bg-success/15 text-success' => $event->status === 'active',
                                'bg-warning/15 text-warning' => $event->status === 'paused',
                                'bg-danger/15 text-danger' => $event->status === 'cancelled',
                            ])>{{ ucfirst($event->status) }}</span>
                        </div>
                        <div class="mt-3 pt-3 border-t border-line flex gap-2 text-xs">
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
                    <x-list-detail-empty message="No events yet." />
                @endforelse
            </div>
        </x-slot:list>

        <x-slot:detail>
            @if ($selectedEvent)
                @include('livewire.events.partials.event-summary', ['event' => $selectedEvent])
            @endif
        </x-slot:detail>
    </x-list-detail-shell>
</div>
