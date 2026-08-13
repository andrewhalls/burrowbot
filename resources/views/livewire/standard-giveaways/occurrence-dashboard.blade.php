<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">{{ $occurrence->title }}</h2>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by member&hellip;"
               class="rounded-control bg-surface border border-line px-3 py-2 text-sm">
    </div>

    <div class="rounded-card border border-line p-4">
        <h3 class="font-medium mb-2">
            Winners
            <span class="text-xs text-muted">({{ $winners->count() }})</span>
        </h3>

        <ul class="space-y-1 text-sm">
            @forelse ($winners as $winner)
                <li>
                    {{ $winner->standardGiveawayEntry->discordMember->username }}
                    &mdash; <span class="text-muted">{{ $winner->collectionThemeItem->name }}</span>
                </li>
            @empty
                <li class="text-muted">No winners drawn yet.</li>
            @endforelse
        </ul>
    </div>

    <div class="rounded-card border border-line p-4">
        <h3 class="font-medium mb-2">
            Entrants
            <span class="text-xs text-muted">({{ $entries->count() }})</span>
        </h3>

        <ul class="space-y-1 text-sm">
            @forelse ($entries as $entry)
                <li>{{ $entry->discordMember->username }}</li>
            @empty
                <li class="text-muted">No entrants yet.</li>
            @endforelse
        </ul>
    </div>
</div>
