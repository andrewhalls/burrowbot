<div class="space-y-6">
    <div class="rounded-card border border-line p-4 space-y-4 max-w-md">
        <h2 class="text-lg font-semibold">Guild settings</h2>

        <div>
            <x-channel-picker :guild="$guild" model="defaultChannelId" :value="$defaultChannelId" label="Default giveaway channel" />
            <p class="text-xs text-muted mt-1">Pre-fills new giveaway drafts; can still be overridden per giveaway.</p>
        </div>

        <div class="pt-2 border-t border-line">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="popupGiveawayWinnerMessagesEnabled">
                Popup giveaway per-winner messages
            </label>
            <p class="text-xs text-muted mt-1">Lets popup giveaways send a templated message to a chosen channel every time someone wins. Turning this off immediately stops those messages, even for giveaways that already have it configured.</p>
        </div>

        <button type="button" wire:click="save" class="rounded-control bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
            Save
        </button>
    </div>
</div>
