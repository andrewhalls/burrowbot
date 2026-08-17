<div class="rounded-card border border-line p-4 space-y-4 max-w-xl">
    <div>
        <h3 class="text-lg font-semibold text-ink">Edit occurrence</h3>
        <p class="text-xs text-muted mt-1">
            Changes here apply to this occurrence only (<x-local-time :at="$occurrence->scheduled_post_at" />) - the series template and every other occurrence are unaffected.
        </p>
    </div>

    <div>
        <label class="block text-sm text-muted mb-1">Description</label>
        <textarea wire:model="description" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm"></textarea>
        @error('description') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm text-muted mb-1">Image (optional)</label>
        <input type="file" wire:model="image" accept="image/*" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
        @error('image') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
        @if ($image && $image->isPreviewable())
            <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="mt-2 h-24 rounded-control object-cover">
        @elseif ($occurrence->image_url)
            <img src="{{ $occurrence->image_url }}" alt="Current image" class="mt-2 h-24 rounded-control object-cover">
        @endif
    </div>

    <div>
        <label class="block text-sm text-muted mb-1">Prize items</label>
        <input type="text" wire:model.live.debounce.300ms="prizeItemSearch" placeholder="Search collection theme items&hellip;"
               class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm mb-2">

        @if ($this->searchResults->isNotEmpty())
            <ul class="mb-2 rounded-control border border-line divide-y divide-line">
                @foreach ($this->searchResults as $item)
                    <li class="flex items-center justify-between px-3 py-2 text-sm">
                        <span>{{ $item->name }} <span class="text-muted">({{ $item->collectionTheme->name }})</span></span>
                        <button type="button" wire:click="addPrizeItem({{ $item->id }})" class="text-xs text-accent hover:text-accent">Add</button>
                    </li>
                @endforeach
            </ul>
        @endif

        <ul class="flex flex-wrap gap-2">
            @foreach ($selectedPrizeItemIds as $itemId)
                <li wire:key="selected-item-{{ $itemId }}" class="inline-flex items-center gap-1 rounded-full bg-surface-hover px-3 py-1 text-xs">
                    #{{ $itemId }}
                    <button type="button" wire:click="removePrizeItem({{ $itemId }})" class="text-muted hover:text-danger">&times;</button>
                </li>
            @endforeach
        </ul>
        @error('selectedPrizeItemIds') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <button type="button" wire:click="save" class="rounded-control bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
        Save changes
    </button>
</div>
