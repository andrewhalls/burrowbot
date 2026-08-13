<div class="space-y-4">
    <h2 class="text-lg font-semibold">Entrants</h2>

    <div class="flex flex-wrap gap-3">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by member&hellip;"
               class="rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">

        <select wire:model.live="itemFilter" class="rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
            <option value="">All items</option>
            @foreach ($items as $item)
                <option value="{{ $item->id }}">{{ $item->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="fulfilmentFilter" class="rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
            <option value="all">All</option>
            <option value="unfulfilled">Not yet handed out</option>
            <option value="fulfilled">Handed out</option>
        </select>
    </div>

    <table class="w-full text-sm">
        <thead class="text-left text-neutral-400">
            <tr>
                <th class="py-2">Member</th>
                <th class="py-2">Item</th>
                <th class="py-2">Entered</th>
                <th class="py-2">Status</th>
                <th class="py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-800">
            @forelse ($entries as $entry)
                <tr wire:key="entry-{{ $entry->id }}">
                    <td class="py-2">{{ $entry->discordMember->username }}</td>
                    <td class="py-2">{{ $entry->collectionThemeItem?->name ?? '—' }}</td>
                    <td class="py-2">{{ $entry->created_at->diffForHumans() }}</td>
                    <td class="py-2">
                        @if ($entry->isFulfilled())
                            <span class="text-emerald-400">Handed out{{ $entry->fulfilledBy ? ' by '.$entry->fulfilledBy->name : '' }}</span>
                        @else
                            <span class="text-neutral-500">Pending</span>
                        @endif
                    </td>
                    <td class="py-2 text-right">
                        @unless ($entry->isFulfilled())
                            <button type="button" wire:click="markFulfilled({{ $entry->id }})"
                                    class="text-xs rounded bg-neutral-800 hover:bg-neutral-700 px-3 py-1.5">
                                Mark handed out
                            </button>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-6 text-center text-neutral-500">No entrants match.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $entries->links() }}
</div>
