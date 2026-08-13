<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">{{ $occurrence->title }}</h2>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by member&hellip;"
               class="rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
    </div>

    <div class="rounded-lg border border-neutral-800 p-4">
        <h3 class="font-medium mb-2">
            Winners
            <span class="text-xs text-neutral-500">({{ $winners->count() }})</span>
        </h3>

        <ul class="space-y-1 text-sm">
            @forelse ($winners as $winner)
                <li>
                    {{ $winner->standardGiveawayEntry->discordMember->username }}
                    &mdash; <span class="text-neutral-400">{{ $winner->collectionThemeItem->name }}</span>
                </li>
            @empty
                <li class="text-neutral-500">No winners drawn yet.</li>
            @endforelse
        </ul>
    </div>

    <div class="rounded-lg border border-neutral-800 p-4">
        <h3 class="font-medium mb-2">
            Entrants
            <span class="text-xs text-neutral-500">({{ $entries->count() }})</span>
        </h3>

        <ul class="space-y-1 text-sm">
            @forelse ($entries as $entry)
                <li>{{ $entry->discordMember->username }}</li>
            @empty
                <li class="text-neutral-500">No entrants yet.</li>
            @endforelse
        </ul>
    </div>
</div>
