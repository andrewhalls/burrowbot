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

    <ul class="divide-y divide-line rounded-card border border-line">
        @forelse ($events as $event)
            <li class="p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div>
                        <p class="font-medium text-ink">{{ $event->title }}</p>
                        <p class="text-xs text-muted">
                            {{ $event->eventRoleSet->name }} &middot;
                            {{ $event->isRecurring() ? 'Recurring' : 'One-off' }}
                        </p>
                    </div>
                    <span @class([
                        'rounded-pill px-2.5 py-1 text-xs font-medium',
                        'bg-success/15 text-success' => $event->status === 'active',
                        'bg-warning/15 text-warning' => $event->status === 'paused',
                        'bg-danger/15 text-danger' => $event->status === 'cancelled',
                    ])>{{ ucfirst($event->status) }}</span>
                </div>
                <div class="flex gap-2 text-xs">
                    @if ($event->status !== 'active')
                        <button type="button" wire:click="setStatus({{ $event->id }}, 'active')" class="text-success hover:text-success">Activate</button>
                    @endif
                    @if ($event->status !== 'paused')
                        <button type="button" wire:click="setStatus({{ $event->id }}, 'paused')" class="text-warning hover:text-warning">Pause</button>
                    @endif
                    @if ($event->status !== 'cancelled')
                        <button type="button" wire:click="setStatus({{ $event->id }}, 'cancelled')" wire:confirm="Cancel this event?" class="text-danger hover:text-danger">Cancel</button>
                    @endif
                </div>
            </li>
        @empty
            <li class="p-4 text-sm text-muted">No events yet.</li>
        @endforelse
    </ul>
</div>
