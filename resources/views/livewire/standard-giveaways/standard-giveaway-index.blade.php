<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Standard giveaways</h2>
        <button type="button" wire:click="$toggle('showCreateForm')" class="text-sm text-accent hover:text-accent">
            {{ $showCreateForm ? 'Cancel' : '+ New giveaway' }}
        </button>
    </div>

    @if ($showCreateForm)
        <livewire:standard-giveaways.create-standard-giveaway :guild="$guild" :key="'create-std-giveaway-'.$guild->id" />
    @endif

    <ul class="divide-y divide-line rounded-card border border-line">
        @forelse ($giveaways as $giveaway)
            <li class="p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium">{{ $giveaway->title }}</p>
                    <p class="text-xs text-muted">
                        {{ $giveaway->prize_items_count }} prize item(s) &middot;
                        {{ $giveaway->winner_count }} winner(s) &middot;
                        {{ $giveaway->requires_booster ? 'Boosters only &middot; ' : '' }}
                        {{ $giveaway->isRecurring() ? 'Recurring' : 'One-off' }} &middot;
                        {{ ucfirst($giveaway->status) }}
                    </p>
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
