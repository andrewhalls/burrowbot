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
                        <span @class([
                            'rounded-pill px-2 py-0.5 text-[11px] font-medium shrink-0 self-start mt-1',
                            'bg-surface-hover text-muted' => $giveaway->status === 'draft',
                            'bg-success/15 text-success' => $giveaway->status === 'active',
                            'bg-line text-muted' => $giveaway->status === 'closed',
                        ])>{{ ucfirst($giveaway->status) }}</span>
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
            @if ($selectedGiveaway)
                <livewire:giveaways.giveaway-dashboard :giveaway="$selectedGiveaway" :key="'giveaway-detail-'.$selectedGiveaway->id" />
            @endif
        </x-slot:detail>
    </x-list-detail-shell>
</div>
