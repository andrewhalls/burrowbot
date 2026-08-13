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
            <label class="block text-sm text-neutral-400 mb-1">Role set</label>
            <select wire:model="eventRoleSetId" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
                <option value="">Select&hellip;</option>
                @foreach ($roleSets as $roleSet)
                    <option value="{{ $roleSet->id }}">{{ $roleSet->name }}</option>
                @endforeach
            </select>
            @error('eventRoleSetId') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm text-neutral-400 mb-1">Posting mode</label>
        <select wire:model="postingMode" class="w-full rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
            <option value="message">New message per occurrence</option>
            <option value="thread">New thread per occurrence</option>
        </select>
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
        Create event
    </button>
</div>
