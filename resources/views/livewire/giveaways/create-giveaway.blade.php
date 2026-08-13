<div class="space-y-6">
    <div class="rounded-card border border-line p-4 space-y-4">
        <div>
            <label class="block text-sm text-muted mb-1">Discord channel ID</label>
            <input type="text" wire:model="channelId" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
            @error('channelId') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm text-muted mb-1">Themed collection</label>
            <select wire:model="collectionThemeId" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
                <option value="">Select a theme&hellip;</option>
                @foreach ($themes as $theme)
                    <option value="{{ $theme->id }}">{{ $theme->name }}</option>
                @endforeach
            </select>
            @error('collectionThemeId') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm text-muted mb-1">Duration (minutes)</label>
            <input type="number" min="1" wire:model="durationMinutes" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
            @error('durationMinutes') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="button" wire:click="save" class="rounded-control bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium">
            Create giveaway
        </button>
    </div>
</div>
