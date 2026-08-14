<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Standard giveaways</h2>
        <button type="button" wire:click="$toggle('showCreateForm')" class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
            {{ $showCreateForm ? 'Cancel' : '+ New giveaway' }}
        </button>
    </div>

    @if ($showCreateForm)
        <livewire:standard-giveaways.create-standard-giveaway :guild="$guild" :key="'create-std-giveaway-'.$guild->id" />
    @endif

    <ul class="divide-y divide-line rounded-card border border-line">
        @forelse ($giveaways as $giveaway)
            <li class="p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    @if ($giveaway->image_url)
                        <img src="{{ $giveaway->image_url }}" alt="" class="h-12 w-12 rounded-control object-cover shrink-0">
                    @endif
                    <div>
                        <p class="font-medium text-ink">{{ $giveaway->title }}</p>
                        <p class="text-xs text-muted">
                            {{ $giveaway->prize_items_count }} prize item(s) &middot;
                            {{ $giveaway->winner_count }} winner(s) &middot;
                            {{ $giveaway->requires_booster ? 'Boosters only &middot; ' : '' }}
                            {{ $giveaway->isRecurring() ? 'Recurring' : 'One-off' }}
                        </p>
                        @if ($giveaway->description)
                            <p class="text-xs text-muted mt-1">{{ $giveaway->description }}</p>
                        @endif
                    </div>
                    <span @class([
                        'rounded-pill px-2.5 py-1 text-xs font-medium',
                        'bg-success/15 text-success' => $giveaway->status === 'active',
                        'bg-warning/15 text-warning' => $giveaway->status === 'paused',
                        'bg-danger/15 text-danger' => $giveaway->status === 'cancelled',
                    ])>{{ ucfirst($giveaway->status) }}</span>
                </div>
                <div class="flex gap-2 text-xs">
                    @if ($giveaway->status !== 'active')
                        <button type="button" wire:click="setStatus({{ $giveaway->id }}, 'active')" class="text-success hover:text-success">Activate</button>
                    @endif
                    @if ($giveaway->status !== 'paused')
                        <button type="button" wire:click="setStatus({{ $giveaway->id }}, 'paused')" class="text-warning hover:text-warning">Pause</button>
                    @endif
                    @if ($giveaway->status !== 'cancelled')
                        <button type="button" wire:click="setStatus({{ $giveaway->id }}, 'cancelled')" wire:confirm="Cancel this giveaway?" class="text-danger hover:text-danger">Cancel</button>
                    @endif
                </div>
            </li>
        @empty
            <li class="p-4 text-sm text-muted">No standard giveaways yet.</li>
        @endforelse
    </ul>
</div>
