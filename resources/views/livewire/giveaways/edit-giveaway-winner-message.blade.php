<div class="rounded-card border border-line p-4 space-y-4 max-w-xl">
    <div>
        <h3 class="text-lg font-semibold text-ink">Winner message</h3>
        <p class="text-xs text-muted mt-1">
            Sends a message to the channel below every time someone wins, in addition to the normal public announcement. Available at any giveaway status. Setting one of these two fields requires the other.
        </p>
    </div>

    <x-channel-picker :guild="$guild" model="winnerMessageChannelId" label="Winner message channel" :value="$winnerMessageChannelId" />

    <div>
        <label class="block text-sm text-muted mb-1">Winner message template</label>
        <textarea wire:model="winnerMessageTemplate" placeholder="Congrats {winner}! You won {prize}." class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm"></textarea>
        <p class="text-xs text-muted mt-1">Placeholders: <code>{winner}</code>, <code>{prize}</code>.</p>
        @error('winnerMessageTemplate') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <button type="button" wire:click="save" class="rounded-control bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
        Save changes
    </button>
</div>
