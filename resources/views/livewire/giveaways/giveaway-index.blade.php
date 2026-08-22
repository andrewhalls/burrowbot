<div class="space-y-6">
    <div class="flex items-center justify-between gap-2">
        <h2 class="text-lg font-semibold text-ink">Popup giveaways</h2>
        <div class="flex items-center gap-2">
            @error('delete') <p class="text-xs text-danger">{{ $message }}</p> @enderror
            @if ($selectedGiveaway && ! $showCreateForm && $guild->popup_giveaway_winner_messages_enabled)
                <button type="button" wire:click="toggleEditWinnerMessage"
                        class="rounded-pill bg-surface-hover hover:bg-line px-4 py-2 text-sm font-medium text-ink">
                    {{ $editingWinnerMessage ? 'Cancel' : 'Winner message' }}
                </button>
            @endif
            @if ($selectedGiveaway && $selectedGiveaway->isDraft() && ! $showCreateForm)
                <button type="button" wire:click="toggleEdit"
                        class="rounded-pill bg-surface-hover hover:bg-line px-4 py-2 text-sm font-medium text-ink">
                    {{ $editing ? 'Cancel' : 'Edit' }}
                </button>
                <button type="button" wire:click="start({{ $selectedGiveaway->id }})" wire:confirm="Start this popup giveaway now?"
                        class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
                    Start
                </button>
                <button type="button" wire:click="delete" wire:confirm="Delete this draft giveaway? This cannot be undone."
                        class="rounded-pill border border-line text-danger hover:bg-danger/10 px-4 py-2 text-sm font-medium">
                    Delete
                </button>
            @endif
            <button type="button" wire:click="toggleCreateForm" class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
                {{ $showCreateForm ? 'Cancel' : '+ New giveaway' }}
            </button>
        </div>
    </div>

    <x-list-detail-shell :selected="$selectedGiveaway !== null || $showCreateForm">
        <x-slot:list>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse ($giveaways as $giveaway)
                    <div wire:key="giveaway-tile-{{ $giveaway->id }}" wire:click="select({{ $giveaway->id }})"
                         @class([
                            'rounded-card border p-3 cursor-pointer hover:bg-surface-hover transition-colors flex flex-col',
                            'border-accent' => $selectedGiveaway?->id === $giveaway->id,
                            'border-line' => $selectedGiveaway?->id !== $giveaway->id,
                         ])>
                        @if ($giveaway->image_url)
                            <img src="{{ $giveaway->image_url }}" alt="" class="w-full h-20 rounded-control object-cover mb-2">
                        @endif
                        <div class="flex items-start justify-between gap-1">
                            <p class="font-medium text-ink text-sm truncate">{{ $giveaway->collectionTheme->name }}</p>
                        </div>
                        <div class="flex items-center gap-1 flex-wrap mt-1">
                            <span @class([
                                'rounded-pill px-2 py-0.5 text-[11px] font-medium shrink-0',
                                'bg-surface-hover text-muted' => $giveaway->status === 'draft',
                                'bg-success/15 text-success' => $giveaway->status === 'active',
                                'bg-line text-muted' => $giveaway->status === 'closed',
                            ])>{{ ucfirst($giveaway->status) }}</span>
                            @if ($giveaway->hasWinnerMessageConfigured())
                                <span class="rounded-pill px-2 py-0.5 text-[11px] font-medium shrink-0 bg-surface-hover text-muted">Winner message on</span>
                            @endif
                        </div>
                        <p class="text-xs text-muted mt-2">
                            {{ $giveaway->entries_count }} {{ Str::plural('entrant', $giveaway->entries_count) }} &middot;
                            {{ $giveaway->duration_minutes }} min
                        </p>
                        @if ($giveaway->scheduled_start_at)
                            <p class="text-xs text-muted">Scheduled <x-local-time :at="$giveaway->scheduled_start_at" /></p>
                        @endif
                        @if ($giveaway->description)
                            <p class="text-xs text-muted mt-1 truncate">{{ $giveaway->description }}</p>
                        @endif
                        @if ($giveaway->creator)
                            <p class="text-xs text-muted mt-1 truncate">Created by {{ $giveaway->creator->name }}</p>
                        @endif
                        @if ($giveaway->status === 'draft')
                            <div class="mt-auto pt-2">
                                <button type="button" wire:click.stop="start({{ $giveaway->id }})" wire:confirm="Start this popup giveaway now?" class="text-xs text-success hover:text-success font-medium">
                                    Start
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="col-span-2">
                        <x-list-detail-empty message="No popup giveaways yet." />
                    </div>
                @endforelse
            </div>
        </x-slot:list>

        <x-slot:detail>
            @if ($showCreateForm)
                <livewire:giveaways.create-giveaway :guild="$guild" :key="'create-giveaway-'.$guild->id" />
            @elseif ($editingWinnerMessage && $selectedGiveaway)
                <livewire:giveaways.edit-giveaway-winner-message :giveaway="$selectedGiveaway" :key="'edit-giveaway-winner-message-'.$selectedGiveaway->id" />
            @elseif ($editing && $selectedGiveaway)
                <livewire:giveaways.edit-giveaway :giveaway="$selectedGiveaway" :key="'edit-giveaway-'.$selectedGiveaway->id" />
            @elseif ($selectedGiveaway)
                <livewire:giveaways.giveaway-dashboard :giveaway="$selectedGiveaway" :key="'giveaway-detail-'.$selectedGiveaway->id" />
            @endif
        </x-slot:detail>
    </x-list-detail-shell>
</div>
