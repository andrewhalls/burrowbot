<div class="space-y-6">
    <div class="flex items-center justify-between gap-2">
        <h2 class="text-lg font-semibold">Broadcasts</h2>
        <div class="flex items-center gap-2">
            @error('delete') <p class="text-xs text-danger">{{ $message }}</p> @enderror
            @if ($selectedBroadcast && ! $showCreateForm)
                <button type="button" wire:click="toggleEdit"
                        class="rounded-pill bg-surface-hover hover:bg-line px-4 py-2 text-sm font-medium text-ink">
                    {{ $editing ? 'Cancel' : 'Edit' }}
                </button>
                @if ($selectedBroadcast->isDeletable())
                    <button type="button" wire:click="delete" wire:confirm="Delete this broadcast? This cannot be undone."
                            class="rounded-pill border border-line text-danger hover:bg-danger/10 px-4 py-2 text-sm font-medium">
                        Delete
                    </button>
                @endif
            @endif
            <button type="button" wire:click="toggleCreateForm" class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
                {{ $showCreateForm ? 'Cancel' : '+ New broadcast' }}
            </button>
        </div>
    </div>

    <x-list-detail-shell :selected="$selectedBroadcast !== null || $showCreateForm">
        <x-slot:list>
            <label class="flex items-center gap-2 text-xs text-muted mb-3">
                <input type="checkbox" wire:model.live="showArchived">
                Show archived
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse ($broadcasts as $broadcast)
                    <div wire:key="broadcast-tile-{{ $broadcast->id }}" wire:click="select({{ $broadcast->id }})"
                         @class([
                            'rounded-card border p-3 cursor-pointer hover:bg-surface-hover transition-colors flex flex-col',
                            'border-accent' => $selectedBroadcast?->id === $broadcast->id,
                            'border-line' => $selectedBroadcast?->id !== $broadcast->id,
                            'opacity-60' => $broadcast->isArchived(),
                         ])>
                        <p class="font-medium text-ink text-sm truncate">{{ $broadcast->title }}</p>
                        <div class="flex items-center gap-1 flex-wrap mt-1">
                            <span @class([
                                'rounded-pill px-2 py-0.5 text-[11px] font-medium shrink-0',
                                'bg-success/15 text-success' => $broadcast->status === 'active',
                                'bg-warning/15 text-warning' => $broadcast->status === 'paused',
                                'bg-danger/15 text-danger' => $broadcast->status === 'cancelled',
                            ])>{{ ucfirst($broadcast->status) }}</span>
                            @if ($broadcast->isArchived())
                                <span class="rounded-pill px-2 py-0.5 text-[11px] font-medium shrink-0 bg-surface-hover text-muted">Archived</span>
                            @endif
                        </div>
                        <p class="text-xs text-muted mt-2">
                            {{ $broadcast->isRecurring() ? 'Recurring' : 'One-off' }}
                        </p>
                        @if ($broadcast->creator)
                            <p class="text-xs text-muted mt-1 truncate">Created by {{ $broadcast->creator->name }}</p>
                        @endif
                        <div class="mt-auto pt-2 flex gap-2 text-xs flex-wrap">
                            @if ($broadcast->status !== 'active')
                                <button type="button" wire:click.stop="setStatus({{ $broadcast->id }}, 'active')" class="text-success hover:text-success">Activate</button>
                            @endif
                            @if ($broadcast->status !== 'paused')
                                <button type="button" wire:click.stop="setStatus({{ $broadcast->id }}, 'paused')" class="text-warning hover:text-warning">Pause</button>
                            @endif
                            @if ($broadcast->status !== 'cancelled')
                                <button type="button" wire:click.stop="setStatus({{ $broadcast->id }}, 'cancelled')" wire:confirm="Cancel this broadcast?" class="text-danger hover:text-danger">Cancel</button>
                            @endif
                            @if ($broadcast->isArchived())
                                <button type="button" wire:click.stop="unarchive({{ $broadcast->id }})" class="text-muted hover:text-ink">Unarchive</button>
                            @else
                                <button type="button" wire:click.stop="archive({{ $broadcast->id }})" wire:confirm="Archive this broadcast? This also cancels it." class="text-muted hover:text-ink">Archive</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-2">
                        <x-list-detail-empty message="No broadcasts yet." />
                    </div>
                @endforelse
            </div>
        </x-slot:list>

        <x-slot:detail>
            @if ($showCreateForm)
                <livewire:broadcasts.create-broadcast :guild="$guild" :key="'create-broadcast-'.$guild->id" />
            @elseif ($editing && $selectedBroadcast)
                <livewire:broadcasts.edit-broadcast :broadcast="$selectedBroadcast" :key="'edit-broadcast-'.$selectedBroadcast->id" />
            @elseif ($selectedBroadcast)
                @include('livewire.broadcasts.partials.broadcast-summary', ['broadcast' => $selectedBroadcast])
            @endif
        </x-slot:detail>
    </x-list-detail-shell>
</div>
