<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-ink">Popup giveaways</h2>
        <button type="button" wire:click="$toggle('showCreateForm')" class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
            {{ $showCreateForm ? 'Cancel' : '+ New giveaway' }}
        </button>
    </div>

    @if ($showCreateForm)
        <livewire:giveaways.create-giveaway :guild="$guild" :key="'create-giveaway-'.$guild->id" />
    @endif

    <x-list-detail-shell :selected="$selectedGiveaway !== null">
        <x-slot:list>
            <div class="space-y-3">
                @forelse ($giveaways as $giveaway)
                    <div wire:key="giveaway-tile-{{ $giveaway->id }}" wire:click="select({{ $giveaway->id }})"
                         @class([
                            'rounded-card border p-4 cursor-pointer hover:bg-surface-hover transition-colors',
                            'border-accent' => $selectedGiveaway?->id === $giveaway->id,
                            'border-line' => $selectedGiveaway?->id !== $giveaway->id,
                         ])>
                        <div class="flex items-center gap-3">
                            @if ($giveaway->image_url)
                                <img src="{{ $giveaway->image_url }}" alt="" class="h-12 w-12 rounded-control object-cover shrink-0">
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-ink truncate">{{ $giveaway->collectionTheme->name }} giveaway</p>
                                <p class="text-xs text-muted">
                                    {{ $giveaway->entries_count }} {{ Str::plural('entrant', $giveaway->entries_count) }} &middot;
                                    {{ $giveaway->duration_minutes }} min
                                    @if ($giveaway->scheduled_start_at)
                                        &middot; Scheduled for <x-local-time :at="$giveaway->scheduled_start_at" />
                                    @endif
                                </p>
                                @if ($giveaway->description)
                                    <p class="text-xs text-muted mt-1 truncate">{{ $giveaway->description }}</p>
                                @endif
                            </div>
                            <span @class([
                                'rounded-pill px-2.5 py-1 text-xs font-medium shrink-0',
                                'bg-surface-hover text-muted' => $giveaway->status === 'draft',
                                'bg-success/15 text-success' => $giveaway->status === 'active',
                                'bg-line text-muted' => $giveaway->status === 'closed',
                            ])>{{ ucfirst($giveaway->status) }}</span>
                        </div>
                        @if ($giveaway->status === 'draft')
                            <div class="mt-3 pt-3 border-t border-line">
                                <button type="button" wire:click.stop="start({{ $giveaway->id }})" wire:confirm="Start this popup giveaway now?" class="text-xs text-success hover:text-success font-medium">
                                    Start
                                </button>
                            </div>
                        @endif
                    </div>
                @empty
                    <x-list-detail-empty message="No popup giveaways yet." />
                @endforelse
            </div>
        </x-slot:list>

        <x-slot:detail>
            @if ($selectedGiveaway)
                <livewire:giveaways.giveaway-dashboard :giveaway="$selectedGiveaway" :key="'giveaway-detail-'.$selectedGiveaway->id" />
            @endif
        </x-slot:detail>
    </x-list-detail-shell>
</div>
