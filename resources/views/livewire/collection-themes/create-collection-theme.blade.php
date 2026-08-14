<div class="rounded-card border border-line p-4 space-y-4">
    <div>
        <label class="block text-sm text-muted mb-1">Theme name</label>
        <input type="text" wire:model="name" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
        @error('name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm text-muted mb-1">Image (optional)</label>
        <input type="file" wire:model="image" accept="image/*" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
        @error('image') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
        @if ($image && $image->isPreviewable())
            <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="mt-2 h-24 rounded-control object-cover">
        @endif
    </div>

    <div>
        <label class="block text-sm text-muted mb-1">Items</label>
        <div class="space-y-2">
            @foreach ($items as $index => $item)
                <div class="flex gap-2">
                    <input type="text" wire:model="items.{{ $index }}" placeholder="Item name"
                           class="flex-1 rounded-control bg-surface border border-line px-3 py-2 text-sm">
                    @if (count($items) > 1)
                        <button type="button" wire:click="removeItemRow({{ $index }})" class="text-muted hover:text-danger px-2">&times;</button>
                    @endif
                </div>
            @endforeach
        </div>
        @error('items') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror

        <button type="button" wire:click="addItemRow" class="mt-2 text-xs text-accent hover:text-accent">+ Add item</button>
    </div>

    <button type="button" wire:click="save" class="rounded-control bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
        Create theme
    </button>
</div>
