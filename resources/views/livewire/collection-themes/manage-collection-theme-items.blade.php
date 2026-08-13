<div>
    <div class="flex items-center justify-between mb-2">
        <h3 class="font-medium">{{ $theme->name }}</h3>
        @unless ($editable)
            <span class="text-xs text-warning">Locked while an active giveaway uses this theme</span>
        @endunless
    </div>

    <ul class="flex flex-wrap gap-2 mb-3">
        @foreach ($items as $item)
            <li class="inline-flex items-center gap-1 rounded-full bg-surface-hover px-3 py-1 text-xs">
                {{ $item->name }}
                @if ($editable)
                    <button type="button" wire:click="removeItem({{ $item->id }})" wire:confirm="Remove this item?" class="text-muted hover:text-danger">&times;</button>
                @endif
            </li>
        @endforeach
    </ul>

    @if ($editable)
        <div class="flex gap-2">
            <input type="text" wire:model="newItemName" placeholder="New item"
                   class="flex-1 rounded-control bg-surface border border-line px-3 py-1.5 text-sm">
            <button type="button" wire:click="addItem" class="rounded-control bg-surface-hover hover:bg-surface-hover px-3 py-1.5 text-sm">Add</button>
        </div>
        @error('newItemName') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
    @endif
</div>
