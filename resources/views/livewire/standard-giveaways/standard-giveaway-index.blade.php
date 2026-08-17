<div class="space-y-6">
    <div class="flex items-center justify-between gap-2">
        <h2 class="text-lg font-semibold">Standard giveaways</h2>
        <div class="flex items-center gap-2">
            @error('delete') <p class="text-xs text-danger">{{ $message }}</p> @enderror
            @if ($selectedGiveaway && ! $showCreateForm && ! $editingOccurrence)
                <button type="button" wire:click="toggleEditSeries"
                        class="rounded-pill bg-surface-hover hover:bg-line px-4 py-2 text-sm font-medium text-ink">
                    {{ $editingSeries ? 'Cancel' : 'Edit series' }}
                </button>
                @if ($selectedGiveaway->isDeletable())
                    <button type="button" wire:click="delete" wire:confirm="Delete this standard giveaway? This cannot be undone."
                            class="rounded-pill border border-line text-danger hover:bg-danger/10 px-4 py-2 text-sm font-medium">
                        Delete
                    </button>
                @endif
            @endif
            <button type="button" wire:click="toggleCreateForm" class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
                {{ $showCreateForm ? 'Cancel' : '+ New giveaway' }}
            </button>
        </div>
    </div>

    <x-list-detail-shell :selected="$selectedGiveaway !== null || $showCreateForm">
        <x-slot:list>
            <label class="flex items-center gap-2 text-xs text-muted mb-3">
                <input type="checkbox" wire:model.live="showArchived">
                Show archived
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse ($giveaways as $giveaway)
                    <div wire:key="std-giveaway-tile-{{ $giveaway->id }}" wire:click="select({{ $giveaway->id }})"
                         @class([
                            'rounded-card border p-3 cursor-pointer hover:bg-surface-hover transition-colors flex flex-col',
                            'border-accent' => $selectedGiveaway?->id === $giveaway->id,
                            'border-line' => $selectedGiveaway?->id !== $giveaway->id,
                            'opacity-60' => $giveaway->isArchived(),
                         ])>
                        @if ($giveaway->image_url)
                            <img src="{{ $giveaway->image_url }}" alt="" class="w-full h-20 rounded-control object-cover mb-2">
                        @endif
                        <p class="font-medium text-ink text-sm truncate">{{ $giveaway->title }}</p>
                        <div class="flex items-center gap-1 flex-wrap mt-1">
                            <span @class([
                                'rounded-pill px-2 py-0.5 text-[11px] font-medium shrink-0',
                                'bg-success/15 text-success' => $giveaway->status === 'active',
                                'bg-warning/15 text-warning' => $giveaway->status === 'paused',
                                'bg-danger/15 text-danger' => $giveaway->status === 'cancelled',
                            ])>{{ ucfirst($giveaway->status) }}</span>
                            @if ($giveaway->isArchived())
                                <span class="rounded-pill px-2 py-0.5 text-[11px] font-medium shrink-0 bg-surface-hover text-muted">Archived</span>
                            @endif
                        </div>
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
                            @if ($giveaway->isArchived())
                                <button type="button" wire:click.stop="unarchive({{ $giveaway->id }})" class="text-muted hover:text-ink">Unarchive</button>
                            @else
                                <button type="button" wire:click.stop="archive({{ $giveaway->id }})" wire:confirm="Archive this giveaway? This also cancels it." class="text-muted hover:text-ink">Archive</button>
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
            @if ($showCreateForm)
                <livewire:standard-giveaways.create-standard-giveaway :guild="$guild" :key="'create-std-giveaway-'.$guild->id" />
            @elseif ($editingSeries && $selectedGiveaway)
                <livewire:standard-giveaways.edit-standard-giveaway :giveaway="$selectedGiveaway" :key="'edit-std-giveaway-'.$selectedGiveaway->id" />
            @elseif ($editingOccurrence)
                <livewire:standard-giveaways.edit-standard-giveaway-occurrence :occurrence="$editingOccurrence" :key="'edit-std-giveaway-occurrence-'.$editingOccurrence->id" />
            @elseif ($selectedGiveaway)
                <div class="space-y-4">
                    @if ($upcomingOccurrences->isNotEmpty())
                        <div class="rounded-card border border-line divide-y divide-line">
                            <p class="text-xs text-muted px-3 py-2 bg-surface-hover">Upcoming occurrences - edit one in advance</p>
                            @foreach ($upcomingOccurrences as $occurrence)
                                <div wire:key="upcoming-occurrence-{{ $occurrence->id }}" class="p-3 text-sm flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-ink"><x-local-time :at="$occurrence->scheduled_post_at" /></p>
                                        <p class="text-xs text-muted truncate">{{ $occurrence->description }} &middot; {{ count($occurrence->prize_item_ids) }} item(s)</p>
                                    </div>
                                    <button type="button" wire:click="toggleEditOccurrence({{ $occurrence->id }})" class="text-xs text-accent hover:text-accent shrink-0">
                                        Edit
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($selectedOccurrence)
                        <livewire:standard-giveaways.occurrence-dashboard :occurrence="$selectedOccurrence" :key="'std-giveaway-detail-'.$selectedOccurrence->id" />
                    @else
                        <x-list-detail-empty message="No occurrences generated for this giveaway yet." />
                    @endif
                </div>
            @endif
        </x-slot:detail>
    </x-list-detail-shell>
</div>
