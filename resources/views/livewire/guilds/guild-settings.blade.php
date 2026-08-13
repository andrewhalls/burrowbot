<div class="space-y-6">
    <div class="rounded-card border border-line p-4 space-y-4 max-w-md">
        <h2 class="text-lg font-semibold">Guild settings</h2>

        <div>
            <label class="block text-sm text-muted mb-1">Default giveaway channel ID</label>
            <input type="text" wire:model="defaultChannelId" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
            @error('defaultChannelId') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
            <p class="text-xs text-muted mt-1">Pre-fills new giveaway drafts; can still be overridden per giveaway.</p>
        </div>

        <button type="button" wire:click="save" class="rounded-control bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium">
            Save
        </button>
    </div>
</div>
