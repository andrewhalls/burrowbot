<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Events</h2>
        <button type="button" wire:click="$toggle('showCreateForm')" class="text-sm text-indigo-400 hover:text-indigo-300">
            {{ $showCreateForm ? 'Cancel' : '+ New event' }}
        </button>
    </div>

    @if ($showCreateForm)
        <livewire:events.create-event :guild="$guild" :key="'create-event-'.$guild->id" />
    @endif

    <ul class="divide-y divide-neutral-800 rounded-lg border border-neutral-800">
        @forelse ($events as $event)
            <li class="p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium">{{ $event->title }}</p>
                    <p class="text-xs text-neutral-500">
                        {{ $event->eventRoleSet->name }} &middot;
                        {{ $event->isRecurring() ? 'Recurring' : 'One-off' }} &middot;
                        {{ ucfirst($event->status) }}
                    </p>
                </div>
                <div class="flex gap-2 text-xs">
                    @if ($event->status !== 'active')
                        <button type="button" wire:click="setStatus({{ $event->id }}, 'active')" class="text-emerald-400 hover:text-emerald-300">Activate</button>
                    @endif
                    @if ($event->status !== 'paused')
                        <button type="button" wire:click="setStatus({{ $event->id }}, 'paused')" class="text-amber-400 hover:text-amber-300">Pause</button>
                    @endif
                    @if ($event->status !== 'cancelled')
                        <button type="button" wire:click="setStatus({{ $event->id }}, 'cancelled')" wire:confirm="Cancel this event?" class="text-red-400 hover:text-red-300">Cancel</button>
                    @endif
                </div>
            </li>
        @empty
            <li class="p-4 text-sm text-neutral-500">No events yet.</li>
        @endforelse
    </ul>
</div>
