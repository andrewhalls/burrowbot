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

    @include('partials.image-upload-field', ['fieldLabel' => 'Image (optional)', 'fieldModel' => 'image', 'fieldFile' => $image, 'fieldCurrentUrl' => $occurrence->image_url])

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

        @include('partials.prize-item-chips')
        @error('selectedPrizeItemIds') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <button type="button" wire:click="save" class="rounded-control bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
        Save changes
    </button>
</div>
