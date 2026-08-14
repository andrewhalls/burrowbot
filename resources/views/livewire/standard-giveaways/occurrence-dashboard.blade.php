<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold">{{ $occurrence->title }}</h2>
            @if ($occurrence->standardGiveaway->creator)
                <p class="text-xs text-muted">Created by {{ $occurrence->standardGiveaway->creator->name }}</p>
            @endif
        </div>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by member&hellip;"
               class="rounded-control bg-surface border border-line px-3 py-2 text-sm">
    </div>

    <div class="rounded-card border border-line p-4">
        <h3 class="font-medium mb-3">
            Winners
            <span class="text-xs text-muted">({{ $winners->count() }})</span>
        </h3>

        <ul class="space-y-2 text-sm">
            @forelse ($winners as $winner)
                <li class="flex items-center justify-between">
                    <span class="text-ink">{{ $winner->standardGiveawayEntry->discordMember->display_name_or_username }}</span>
                    <span class="rounded-pill bg-success/15 text-success px-2 py-0.5 text-xs font-medium">{{ $winner->collectionThemeItem->name }}</span>
                </li>
            @empty
                <li class="text-muted">No winners drawn yet.</li>
            @endforelse
        </ul>
    </div>

    <div class="rounded-card border border-line p-4">
        <h3 class="font-medium mb-3">
            Entrants
            <span class="text-xs text-muted">({{ $entries->count() }})</span>
        </h3>

        <ul class="divide-y divide-line text-sm">
            @forelse ($entries as $entry)
                <li class="py-2 text-ink">{{ $entry->discordMember->display_name_or_username }}</li>
            @empty
                <li class="text-muted">No entrants yet.</li>
            @endforelse
        </ul>
    </div>
</div>
