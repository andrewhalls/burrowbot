<div class="space-y-6">
    <x-browser-timezone-input />

    <div class="rounded-card border border-line p-4 space-y-4">
        <x-channel-picker :guild="$guild" model="channelId" :value="$channelId" />

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

        <div>
            <label class="block text-sm text-muted mb-1">Description (optional)</label>
            <textarea wire:model="description" placeholder="Shown on the Discord post instead of the default instructions" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm"></textarea>
            @error('description') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm text-muted mb-1">Image (optional)</label>
            <input type="file" wire:model="image" accept="image/*" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
            @error('image') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
            @if ($image && $image->isPreviewable())
                <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="mt-2 h-24 rounded-control object-cover">
            @endif
        </div>

        <div class="pt-2 border-t border-line">
            <p class="text-sm font-medium text-ink mb-1">Scheduled start (optional)</p>
            <p class="text-xs text-muted mb-3">Leave blank to start it manually later. Set a future date/time to have it post automatically.</p>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="block text-sm text-muted mb-1">Date</label>
                    <input type="date" wire:model="scheduledStartDate" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
                    @error('scheduledStartDate') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex-1">
                    <label class="block text-sm text-muted mb-1">Time</label>
                    <input type="time" wire:model="scheduledStartTime" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
                    @error('scheduledStartTime') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        @if ($guild->popup_giveaway_winner_messages_enabled)
            <div class="pt-2 border-t border-line space-y-3">
                <p class="text-sm font-medium text-ink">Per-winner message (optional)</p>
                <p class="text-xs text-muted -mt-2">Sends a message to the channel below every time someone wins, in addition to the normal public announcement. Setting one of these two fields requires the other.</p>

                <x-channel-picker :guild="$guild" model="winnerMessageChannelId" label="Winner message channel" :value="$winnerMessageChannelId" />

                <div>
                    <label class="block text-sm text-muted mb-1">Winner message template</label>
                    <textarea wire:model="winnerMessageTemplate" placeholder="Congrats {winner}! You won {prize}." class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm"></textarea>
                    <p class="text-xs text-muted mt-1">Placeholders: <code>{winner}</code>, <code>{prize}</code>.</p>
                    @error('winnerMessageTemplate') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        @endif

        <button type="button" wire:click="save" class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
            Create popup giveaway
        </button>
    </div>
</div>
