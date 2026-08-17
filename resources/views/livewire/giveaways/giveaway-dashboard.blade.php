<div class="space-y-4">
    <div>
        <h2 class="text-lg font-semibold text-ink">Entrants</h2>
        @if ($giveaway->creator)
            <p class="text-xs text-muted">Created by {{ $giveaway->creator->name }}</p>
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

    <div class="rounded-card border border-line overflow-hidden">
        <table class="w-full text-sm">
            <thead class="text-left text-muted bg-surface-hover">
                <tr>
                    <th class="py-2 px-3 font-medium">Member</th>
                    <th class="py-2 px-3 font-medium">Item</th>
                    <th class="py-2 px-3 font-medium">Entered</th>
                    <th class="py-2 px-3 font-medium">Status</th>
                    <th class="py-2 px-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($entries as $entry)
                    <tr wire:key="entry-{{ $entry->id }}" class="hover:bg-surface-hover transition-colors">
                        <td class="py-2 px-3 text-ink">{{ $entry->discordMember->display_name_or_username }}</td>
                        <td class="py-2 px-3">
                            @if ($entry->collectionThemeItem)
                                <span class="rounded-pill bg-accent/10 text-accent px-2 py-0.5 text-xs font-medium">{{ $entry->collectionThemeItem->name }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="py-2 px-3 text-muted">{{ $entry->created_at->diffForHumans() }}</td>
                        <td class="py-2 px-3">
                            @if ($entry->isFulfilled())
                                <span class="rounded-pill bg-success/15 text-success px-2 py-0.5 text-xs font-medium">
                                    Handed out{{ $entry->fulfilledBy ? ' by '.$entry->fulfilledBy->name : '' }}
                                </span>
                            @else
                                <span class="rounded-pill bg-warning/15 text-warning px-2 py-0.5 text-xs font-medium">Pending</span>
                            @endif
                        </td>
                        <td class="py-2 px-3 text-right">
                            @unless ($entry->isFulfilled())
                                <button type="button" wire:click="markFulfilled({{ $entry->id }})"
                                        class="text-xs rounded-control bg-surface-hover hover:bg-line px-3 py-1.5">
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
    </div>

    {{ $entries->links() }}
</div>
