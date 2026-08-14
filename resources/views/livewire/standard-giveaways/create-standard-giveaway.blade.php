<div class="rounded-card border border-line p-4 space-y-4 max-w-xl">
    <x-browser-timezone-input />

    <div>
        <label class="block text-sm text-muted mb-1">Title</label>
        <input type="text" wire:model="title" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
        @error('title') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm text-muted mb-1">Description</label>
        <textarea wire:model="description" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm"></textarea>
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

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <x-channel-picker :guild="$guild" model="channelId" :value="$channelId" />
        <div>
            <label class="block text-sm text-muted mb-1">Posting mode</label>
            <select wire:model="postingMode" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
                <option value="message">New message per occurrence</option>
                <option value="thread">New thread per occurrence</option>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm text-muted mb-1">Prize items</label>
        <input type="text" wire:model.live.debounce.300ms="prizeItemSearch" placeholder="Search collection theme items&hellip;"
               class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm mb-2">

        @if ($this->searchResults->isNotEmpty())
            <ul class="mb-2 rounded-control border border-line divide-y divide-line">
                @foreach ($this->searchResults as $item)
                    <li class="flex items-center justify-between px-3 py-2 text-sm">
                        <span>{{ $item->name }} <span class="text-muted">({{ $item->collectionTheme->name }})</span></span>
                        <button type="button" wire:click="addPrizeItem({{ $item->id }})" class="text-xs text-accent hover:text-accent">Add</button>
                    </li>
                @endforeach
            </ul>
        @endif

        <ul class="flex flex-wrap gap-2">
            @foreach ($selectedPrizeItemIds as $itemId)
                <li wire:key="selected-item-{{ $itemId }}" class="inline-flex items-center gap-1 rounded-full bg-surface-hover px-3 py-1 text-xs">
                    #{{ $itemId }}
                    <button type="button" wire:click="removePrizeItem({{ $itemId }})" class="text-muted hover:text-danger">&times;</button>
                </li>
            @endforeach
        </ul>
        @error('selectedPrizeItemIds') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-sm text-muted mb-1">Winner count</label>
            <input type="number" min="1" wire:model="winnerCount" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
            @error('winnerCount') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm text-muted mb-1">Duration (minutes)</label>
            <input type="number" min="1" wire:model="durationMinutes" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
            @error('durationMinutes') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="flex items-center gap-2 text-sm mb-2">
            <input type="checkbox" wire:model="requiresBooster">
            Boosters only
        </label>
        <label class="block text-sm text-muted mb-1">Required roles (optional)</label>

        @include('partials.discord-role-search-results')

        <ul class="flex flex-wrap gap-2">
            @foreach ($selectedRoleIds as $roleId)
                <li wire:key="selected-role-{{ $roleId }}" class="inline-flex items-center gap-1 rounded-full bg-surface-hover px-3 py-1 text-xs">
                    {{ $this->selectedRoleModels[$roleId]->name ?? $roleId }}
                    <button type="button" wire:click="removeDiscordRole('{{ $roleId }}')" class="text-muted hover:text-danger">&times;</button>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="block text-sm text-muted mb-1">Start date</label>
            <input type="date" wire:model="startDate" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
            @error('startDate') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm text-muted mb-1">Start time</label>
            <input type="time" wire:model="startTime" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
            @error('startTime') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm text-muted mb-1">Recurrence</label>
        <select wire:model.live="recurrenceType" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
            <option value="none">One-off (does not repeat)</option>
            <option value="daily">Daily</option>
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
        </select>
        @error('recurrenceType') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    @if ($recurrenceType !== 'none')
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="block text-sm text-muted mb-1">Every</label>
                <input type="number" min="1" wire:model="recurrenceInterval" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm text-muted mb-1">Ends</label>
                <select wire:model.live="recurrenceEndType" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
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
            <input type="date" wire:model="recurrenceEndDate" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
        @elseif ($recurrenceEndType === 'after_count')
            <input type="number" min="1" wire:model="recurrenceEndCount" placeholder="Number of occurrences" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
        @endif
    @endif

    <button type="button" wire:click="save" class="rounded-control bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
        Create giveaway
    </button>
</div>
