<div class="rounded-lg border border-neutral-800 p-4 space-y-4">
    <div>
        <label class="block text-sm text-neutral-400 mb-1">Discord channel ID</label>
        <input type="text" wire:model="channelId" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
        @error('channelId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm text-neutral-400 mb-1">Themed collection</label>
        <select wire:model="collectionThemeId" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
            <option value="">Select a theme&hellip;</option>
            @foreach ($themes as $theme)
                <option value="{{ $theme->id }}">{{ $theme->name }}</option>
            @endforeach
        </select>
        @error('collectionThemeId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm text-neutral-400 mb-1">Duration (minutes)</label>
        <input type="number" min="1" wire:model="durationMinutes" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
        @error('durationMinutes') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <button type="button" wire:click="save" class="rounded bg-indigo-600 hover:bg-indigo-500 px-4 py-2 text-sm font-medium">
        Create giveaway
    </button>
</div>
