<div class="rounded-lg border border-neutral-800 p-4 space-y-4 max-w-xl">
    <div>
        <label class="block text-sm text-neutral-400 mb-1">Title</label>
        <input type="text" wire:model="title" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
        @error('title') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm text-neutral-400 mb-1">Description</label>
        <textarea wire:model="description" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm"></textarea>
        @error('description') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm text-neutral-400 mb-1">Discord channel ID</label>
            <input type="text" wire:model="channelId" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
            @error('channelId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm text-neutral-400 mb-1">Posting mode</label>
            <select wire:model="postingMode" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
                <option value="message">New message per occurrence</option>
                <option value="thread">New thread per occurrence</option>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm text-neutral-400 mb-1">Prize items</label>
        <input type="text" wire:model.live.debounce.300ms="prizeItemSearch" placeholder="Search collection theme items&hellip;"
               class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm mb-2">

        @if ($this->searchResults->isNotEmpty())
            <ul class="mb-2 rounded border border-neutral-800 divide-y divide-neutral-800">
                @foreach ($this->searchResults as $item)
                    <li class="flex items-center justify-between px-3 py-2 text-sm">
                        <span>{{ $item->name }} <span class="text-neutral-500">({{ $item->collectionTheme->name }})</span></span>
                        <button type="button" wire:click="addPrizeItem({{ $item->id }})" class="text-xs text-indigo-400 hover:text-indigo-300">Add</button>
                    </li>
                @endforeach
            </ul>
        @endif

        <ul class="flex flex-wrap gap-2">
            @foreach ($selectedPrizeItemIds as $itemId)
                <li wire:key="selected-item-{{ $itemId }}" class="inline-flex items-center gap-1 rounded-full bg-neutral-800 px-3 py-1 text-xs">
                    #{{ $itemId }}
                    <button type="button" wire:click="removePrizeItem({{ $itemId }})" class="text-neutral-500 hover:text-red-400">&times;</button>
                </li>
            @endforeach
        </ul>
        @error('selectedPrizeItemIds') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm text-neutral-400 mb-1">Winner count</label>
            <input type="number" min="1" wire:model="winnerCount" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
            @error('winnerCount') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm text-neutral-400 mb-1">Duration (minutes)</label>
            <input type="number" min="1" wire:model="durationMinutes" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
            @error('durationMinutes') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="flex items-center gap-2 text-sm mb-2">
            <input type="checkbox" wire:model="requiresBooster">
            Boosters only
        </label>
        <label class="block text-sm text-neutral-400 mb-1">Required roles (Discord role IDs, comma or space separated)</label>
        <input type="text" wire:model="requiredRoleIdsInput" placeholder="Leave blank for no role restriction"
               class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
    </div>

    <div class="grid grid-cols-3 gap-3">
        <div>
            <label class="block text-sm text-neutral-400 mb-1">Start date</label>
            <input type="date" wire:model="startDate" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
            @error('startDate') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm text-neutral-400 mb-1">Start time</label>
            <input type="time" wire:model="startTime" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
            @error('startTime') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm text-neutral-400 mb-1">Timezone</label>
            <input type="text" wire:model="timezone" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
            @error('timezone') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm text-neutral-400 mb-1">Recurrence</label>
        <select wire:model.live="recurrenceType" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
            <option value="none">One-off (does not repeat)</option>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
        </select>
        @error('recurrenceType') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    @if ($recurrenceType !== 'none')
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm text-neutral-400 mb-1">Every</label>
                <input type="number" min="1" wire:model="recurrenceInterval" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm text-neutral-400 mb-1">Ends</label>
                <select wire:model.live="recurrenceEndType" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
                    <option value="never">Never</option>
                    <option value="on_date">On date</option>
                    <option value="after_count">After N occurrences</option>
                </select>
            </div>
        </div>

        @if ($recurrenceType === 'weekly')
            <div class="flex gap-2 flex-wrap">
                @foreach (['MO' => 'Mon', 'TU' => 'Tue', 'WE' => 'Wed', 'TH' => 'Thu', 'FR' => 'Fri', 'SA' => 'Sat', 'SU' => 'Sun'] as $code => $label)
                    <label class="flex items-center gap-1 text-xs">
                        <input type="checkbox" wire:model="recurrenceDaysOfWeek" value="{{ $code }}"> {{ $label }}
                    </label>
                @endforeach
            </div>
        @endif

        @if ($recurrenceEndType === 'on_date')
            <input type="date" wire:model="recurrenceEndDate" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
        @elseif ($recurrenceEndType === 'after_count')
            <input type="number" min="1" wire:model="recurrenceEndCount" placeholder="Number of occurrences" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
        @endif
    @endif

    <button type="button" wire:click="save" class="rounded bg-indigo-600 hover:bg-indigo-500 px-4 py-2 text-sm font-medium">
        Create giveaway
    </button>
</div>
