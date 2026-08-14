<div>
    <div class="flex items-center justify-between mb-2">
        <h3 class="font-medium">{{ $theme->name }}</h3>
        @unless ($editable)
            <span class="text-xs text-warning">Locked while an active giveaway uses this theme</span>
        @endunless
    </div>

    <div class="flex items-center gap-3 mb-4">
        @if ($theme->image_url)
            <img src="{{ $theme->image_url }}" alt="" class="h-14 w-14 rounded-control object-cover">
        @endif
        <div class="flex-1">
            <div class="flex gap-2 items-start">
                <input type="file" wire:model="themeImage" accept="image/*"
                       class="flex-1 rounded-control bg-surface border border-line px-3 py-1.5 text-sm">
                <button type="button" wire:click="saveThemeImage" class="rounded-control bg-surface-hover hover:bg-surface-hover px-3 py-1.5 text-sm">
                    {{ $theme->image_url ? 'Replace' : 'Set image' }}
                </button>
                @if ($theme->image_url)
                    <button type="button" wire:click="removeThemeImage" wire:confirm="Remove this theme's image?" class="text-muted hover:text-danger px-2 py-1.5 text-sm">Remove</button>
                @endif
            </div>
            @error('themeImage') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <ul class="flex flex-wrap gap-2 mb-3">
        @foreach ($items as $item)
            <li class="inline-flex items-center gap-2 rounded-full bg-surface-hover px-3 py-1 text-xs">
                @if ($item->image_url)
                    <img src="{{ $item->image_url }}" alt="" class="h-5 w-5 rounded-full object-cover">
                @endif
                {{ $item->name }}
                @if ($editable)
                    <button type="button" wire:click="removeItem({{ $item->id }})" wire:confirm="Remove this item?" class="text-muted hover:text-danger">&times;</button>
                @endif
            </li>
        @endforeach
    </ul>

    @if ($editable)
        <div class="flex gap-2 items-start">
            <input type="text" wire:model="newItemName" placeholder="New item"
                   class="flex-1 rounded-control bg-surface border border-line px-3 py-1.5 text-sm">
            <input type="file" wire:model="newItemImage" accept="image/*"
                   class="flex-1 rounded-control bg-surface border border-line px-3 py-1.5 text-sm">
            <button type="button" wire:click="addItem" class="rounded-control bg-surface-hover hover:bg-surface-hover px-3 py-1.5 text-sm">Add</button>
        </div>
        @error('newItemName') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
        @error('newItemImage') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
    @endif
</div>
