<div class="rounded-lg border border-neutral-800 p-4 space-y-4">
    <div>
        <label class="block text-sm text-neutral-400 mb-1">Theme name</label>
        <input type="text" wire:model="name" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
        @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm text-neutral-400 mb-1">Items</label>
        <div class="space-y-2">
            @foreach ($items as $index => $item)
                <div class="flex gap-2">
                    <input type="text" wire:model="items.{{ $index }}" placeholder="Item name"
                           class="flex-1 rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
                    @if (count($items) > 1)
                        <button type="button" wire:click="removeItemRow({{ $index }})" class="text-neutral-500 hover:text-red-400 px-2">&times;</button>
                    @endif
                </div>
            @endforeach
        </div>
        @error('items') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror

        <button type="button" wire:click="addItemRow" class="mt-2 text-xs text-indigo-400 hover:text-indigo-300">+ Add item</button>
    </div>

    <button type="button" wire:click="save" class="rounded bg-indigo-600 hover:bg-indigo-500 px-4 py-2 text-sm font-medium">
        Create theme
    </button>
</div>
