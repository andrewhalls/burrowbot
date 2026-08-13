<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-ink">Entrants</h2>
        @if ($giveaway->isDraft())
            <button type="button" wire:click="start" wire:confirm="Start this popup giveaway now?"
                    class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
                Start popup giveaway
            </button>
        @endif
    </div>

    <div class="flex flex-wrap gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by member&hellip;"
               class="rounded-control bg-surface border border-line px-3 py-2 text-sm">

        <select wire:model.live="itemFilter" class="rounded-control bg-surface border border-line px-3 py-2 text-sm">
            <option value="">All items</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="fulfilmentFilter" class="rounded-control bg-surface border border-line px-3 py-2 text-sm">
            <option value="all">All</option>
            <option value="unfulfilled">Not yet handed out</option>
            <option value="fulfilled">Handed out</option>
        </select>
    </div>

    <table class="w-full text-sm">
        <thead class="text-left text-muted">
            <tr>
                <th class="py-2">Member</th>
                <th class="py-2">Item</th>
                <th class="py-2">Entered</th>
                <th class="py-2">Status</th>
                <th class="py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-line">
            @forelse ($entries as $entry)
                <tr wire:key="entry-{{ $entry->id }}">
                    <td class="py-2">{{ $entry->discordMember->username }}</td>
                    <td class="py-2">{{ $entry->collectionThemeItem?->name ?? '—' }}</td>
                    <td class="py-2">{{ $entry->created_at->diffForHumans() }}</td>
                    <td class="py-2">
                        @if ($entry->isFulfilled())
                            <span class="text-success">Handed out{{ $entry->fulfilledBy ? ' by '.$entry->fulfilledBy->name : '' }}</span>
                        @else
                            <span class="text-muted">Pending</span>
                        @endif
                    </td>
                    <td class="py-2 text-right">
                        @unless ($entry->isFulfilled())
                            <button type="button" wire:click="markFulfilled({{ $entry->id }})"
                                    class="text-xs rounded-control bg-surface-hover hover:bg-surface-hover px-3 py-1.5">
                                Mark handed out
                            </button>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-6 text-center text-muted">No entrants match.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $entries->links() }}
</div>
