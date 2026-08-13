<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Standard giveaways</h2>
        <button type="button" wire:click="$toggle('showCreateForm')" class="text-sm text-indigo-400 hover:text-indigo-300">
            {{ $showCreateForm ? 'Cancel' : '+ New giveaway' }}
        </button>
    </div>

    @if ($showCreateForm)
        <livewire:standard-giveaways.create-standard-giveaway :guild="$guild" :key="'create-std-giveaway-'.$guild->id" />
    @endif

    <ul class="divide-y divide-neutral-800 rounded-lg border border-neutral-800">
        @forelse ($giveaways as $giveaway)
            <li class="p-4 flex items-center justify-between">
                <div>
                    <p class="font-medium">{{ $giveaway->title }}</p>
                    <p class="text-xs text-neutral-500">
                        {{ $giveaway->prize_items_count }} prize item(s) &middot;
                        {{ $giveaway->winner_count }} winner(s) &middot;
                        {{ $giveaway->requires_booster ? 'Boosters only &middot; ' : '' }}
                        {{ $giveaway->isRecurring() ? 'Recurring' : 'One-off' }} &middot;
                        {{ ucfirst($giveaway->status) }}
                    </p>
                </div>
                <div class="flex gap-2 text-xs">
                    @if ($giveaway->status !== 'active')
                        <button type="button" wire:click="setStatus({{ $giveaway->id }}, 'active')" class="text-emerald-400 hover:text-emerald-300">Activate</button>
                    @endif
                    @if ($giveaway->status !== 'paused')
                        <button type="button" wire:click="setStatus({{ $giveaway->id }}, 'paused')" class="text-amber-400 hover:text-amber-300">Pause</button>
                    @endif
                    @if ($giveaway->status !== 'cancelled')
                        <button type="button" wire:click="setStatus({{ $giveaway->id }}, 'cancelled')" wire:confirm="Cancel this giveaway?" class="text-red-400 hover:text-red-300">Cancel</button>
                    @endif
                </div>
            </li>
        @empty
            <li class="p-4 text-sm text-neutral-500">No standard giveaways yet.</li>
        @endforelse
    </ul>
</div>
