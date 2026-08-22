<div class="rounded-card border border-line p-4 space-y-4 max-w-xl lg:max-w-3xl 2xl:max-w-4xl">
    <x-browser-timezone-input />

    <div>
        <label class="block text-sm text-muted mb-1">Title</label>
        <input type="text" wire:model="title" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
        @error('title') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm text-muted mb-1">Message template</label>
        <textarea wire:model="messageTemplate" rows="4" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm"></textarea>
        @error('messageTemplate') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
        @verbatim
        <p class="text-xs text-muted mt-1">
            Placeholders: <code>{{guild_name}}</code>, <code>{{channel}}</code>,
            <code>{{date}}</code>, <code>{{time}}</code>, <code>{{next_occurrence_date}}</code>
        </p>
        @endverbatim
    </div>

    <x-channel-picker :guild="$guild" model="channelId" :value="$channelId" />

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
        Save changes
    </button>
</div>
