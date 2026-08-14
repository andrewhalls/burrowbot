<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Themed collections</h2>
        <button type="button" wire:click="$toggle('showCreateForm')" class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
            {{ $showCreateForm ? 'Cancel' : '+ New theme' }}
        </button>
    </div>

    @if ($showCreateForm)
        <livewire:collection-themes.create-collection-theme :guild="$guild" :key="'create-theme-'.$guild->id" />
    @endif

    <x-list-detail-shell :selected="$selectedTheme !== null">
        <x-slot:list>
            <div class="space-y-3">
                @forelse ($themes as $theme)
                    <div wire:key="theme-tile-{{ $theme->id }}" wire:click="select({{ $theme->id }})"
                         @class([
                            'rounded-card border p-4 cursor-pointer hover:bg-surface-hover transition-colors',
                            'border-accent' => $selectedTheme?->id === $theme->id,
                            'border-line' => $selectedTheme?->id !== $theme->id,
                         ])>
                        <p class="font-medium text-ink truncate">{{ $theme->name }}</p>
                        <p class="text-xs text-muted">{{ $theme->items_count }} {{ Str::plural('item', $theme->items_count) }}</p>
                    </div>
                @empty
                    <x-list-detail-empty message="No themed collections yet." />
                @endforelse
            </div>
        </x-slot:list>

        <x-slot:detail>
            @if ($selectedTheme)
                <livewire:collection-themes.manage-collection-theme-items :theme="$selectedTheme" :key="'theme-detail-'.$selectedTheme->id" />
            @endif
        </x-slot:detail>
    </x-list-detail-shell>
</div>
