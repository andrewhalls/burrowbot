<div class="rounded-lg border border-neutral-800 p-4 space-y-4 max-w-md">
    <h2 class="text-lg font-semibold">Guild settings</h2>

    <div>
        <label class="block text-sm text-neutral-400 mb-1">Default giveaway channel ID</label>
        <input type="text" wire:model="defaultChannelId" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
        @error('defaultChannelId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        <p class="text-xs text-neutral-500 mt-1">Pre-fills new giveaway drafts; can still be overridden per giveaway.</p>
    </div>

    <button type="button" wire:click="save" class="rounded bg-indigo-600 hover:bg-indigo-500 px-4 py-2 text-sm font-medium">
        Save
    </button>
</div>
