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

    <x-list-detail-shell :selected="$selectedGiveaway !== null">
        <x-slot:list>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse ($giveaways as $giveaway)
                    <div wire:key="std-giveaway-tile-{{ $giveaway->id }}" wire:click="select({{ $giveaway->id }})"
                         @class([
                            'rounded-card border p-3 cursor-pointer hover:bg-surface-hover transition-colors flex flex-col',
                            'border-accent' => $selectedGiveaway?->id === $giveaway->id,
                            'border-line' => $selectedGiveaway?->id !== $giveaway->id,
                         ])>
                        @if ($giveaway->image_url)
                            <img src="{{ $giveaway->image_url }}" alt="" class="w-full h-20 rounded-control object-cover mb-2">
                        @endif
                        <p class="font-medium text-ink text-sm truncate">{{ $giveaway->title }}</p>
                        <span @class([
                            'rounded-pill px-2 py-0.5 text-[11px] font-medium shrink-0 self-start mt-1',
                            'bg-success/15 text-success' => $giveaway->status === 'active',
                            'bg-warning/15 text-warning' => $giveaway->status === 'paused',
                            'bg-danger/15 text-danger' => $giveaway->status === 'cancelled',
                        ])>{{ ucfirst($giveaway->status) }}</span>
                        <p class="text-xs text-muted mt-2">
                            {{ $giveaway->prize_items_count }} prize item(s) &middot;
                            {{ $giveaway->winner_count }} winner(s)
                        </p>
                        <p class="text-xs text-muted">
                            {{ $giveaway->requires_booster ? 'Boosters only &middot; ' : '' }}
                            {{ $giveaway->isRecurring() ? 'Recurring' : 'One-off' }}
                        </p>
                        @if ($giveaway->description)
                            <p class="text-xs text-muted mt-1 truncate">{{ $giveaway->description }}</p>
                        @endif
                        @if ($giveaway->creator)
                            <p class="text-xs text-muted mt-1 truncate">Created by {{ $giveaway->creator->name }}</p>
                        @endif
                        <div class="mt-auto pt-2 flex gap-2 text-xs flex-wrap">
                            @if ($giveaway->status !== 'active')
                                <button type="button" wire:click.stop="setStatus({{ $giveaway->id }}, 'active')" class="text-success hover:text-success">Activate</button>
                            @endif
                            @if ($giveaway->status !== 'paused')
                                <button type="button" wire:click.stop="setStatus({{ $giveaway->id }}, 'paused')" class="text-warning hover:text-warning">Pause</button>
                            @endif
                            @if ($giveaway->status !== 'cancelled')
                                <button type="button" wire:click.stop="setStatus({{ $giveaway->id }}, 'cancelled')" wire:confirm="Cancel this giveaway?" class="text-danger hover:text-danger">Cancel</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-2">
                        <x-list-detail-empty message="No standard giveaways yet." />
                    </div>
                @endforelse
            </div>
        </x-slot:list>

        <x-slot:detail>
            @if ($selectedGiveaway)
                <div class="flex justify-end mb-3">
                    <button type="button" wire:click="toggleEditSeries"
                            class="rounded-pill bg-surface-hover hover:bg-line px-4 py-2 text-sm font-medium text-ink">
                        {{ $editingSeries ? 'Cancel' : 'Edit series' }}
                    </button>
                </div>
            @endif

            @if ($editingSeries && $selectedGiveaway)
                <livewire:standard-giveaways.edit-standard-giveaway :giveaway="$selectedGiveaway" :key="'edit-std-giveaway-'.$selectedGiveaway->id" />
            @elseif ($selectedOccurrence)
                <livewire:standard-giveaways.occurrence-dashboard :occurrence="$selectedOccurrence" :key="'std-giveaway-detail-'.$selectedOccurrence->id" />
            @elseif ($selectedGiveaway)
                <x-list-detail-empty message="No occurrences generated for this giveaway yet." />
            @endif
        </x-slot:detail>
    </x-list-detail-shell>
</div>
