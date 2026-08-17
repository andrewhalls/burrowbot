<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Themed collections</h2>
        <button type="button" wire:click="toggleCreateForm" class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
            {{ $showCreateForm ? 'Cancel' : '+ New theme' }}
        </button>
    </div>

    <x-list-detail-shell :selected="$selectedTheme !== null || $showCreateForm">
        <x-slot:list>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse ($themes as $theme)
                    <div wire:key="theme-tile-{{ $theme->id }}" wire:click="select({{ $theme->id }})"
                         @class([
                            'rounded-card border p-3 cursor-pointer hover:bg-surface-hover transition-colors flex flex-col items-center text-center gap-1',
                            'border-accent' => $selectedTheme?->id === $theme->id,
                            'border-line' => $selectedTheme?->id !== $theme->id,
                         ])>
                        @if ($theme->image_url)
                            <img src="{{ $theme->image_url }}" alt="" class="h-10 w-10 rounded-control object-cover">
                        @else
                            <span class="flex h-10 w-10 items-center justify-center rounded-control bg-accent/10 text-accent">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                                </svg>
                            </span>
                        @endif
                        <p class="font-medium text-ink text-sm truncate w-full">{{ $theme->name }}</p>
                        <p class="text-xs text-muted">{{ $theme->items_count }} {{ Str::plural('item', $theme->items_count) }}</p>
                        <button type="button" wire:click.stop="duplicate({{ $theme->id }})" class="mt-1 text-xs text-accent hover:text-accent">
                            Duplicate
                        </button>
                    </div>
                @empty
                    <div class="col-span-2">
                        <x-list-detail-empty message="No themed collections yet." />
                    </div>
                @endforelse
            </div>
        </x-slot:list>

        <x-slot:detail>
            @if ($showCreateForm)
                <livewire:collection-themes.create-collection-theme :guild="$guild" :key="'create-theme-'.$guild->id" />
            @elseif ($selectedTheme)
                <livewire:collection-themes.manage-collection-theme-items :theme="$selectedTheme" :key="'theme-detail-'.$selectedTheme->id" />
            @endif
        </x-slot:detail>
    </x-list-detail-shell>
</div>
