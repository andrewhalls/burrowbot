@props([
    'guild',
    'model',
    'label' => 'Discord channel',
    'value' => null,
])

@php
    $channels = $guild->channels()->orderBy('name')->get();
    $selectedChannel = $value ? $channels->firstWhere('discord_channel_id', $value) : null;
    $initialDisplay = $selectedChannel ? '#'.$selectedChannel->name : (string) $value;
@endphp

<div class="relative" data-channel-picker>
    <label class="block text-sm text-muted mb-1">{{ $label }}</label>

    <input
        type="text"
        data-channel-picker-search
        autocomplete="off"
        placeholder="Search channels&hellip;"
        value="{{ $initialDisplay }}"
        class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm"
    >

    <input type="hidden" wire:model="{{ $model }}" data-channel-picker-value value="{{ $value }}">

    <ul data-channel-picker-list class="hidden absolute z-20 mt-1 w-full max-h-56 overflow-auto rounded-control border border-line bg-surface shadow-lg">
        @forelse ($channels as $channel)
            <li
                data-channel-picker-option
                data-id="{{ $channel->discord_channel_id }}"
                data-name="{{ $channel->name }}"
                class="px-3 py-2 text-sm cursor-pointer hover:bg-surface-hover"
            >#{{ $channel->name }}</li>
        @empty
            <li data-channel-picker-empty class="px-3 py-2 text-sm text-muted">No synced channels yet.</li>
        @endforelse
    </ul>

    @error($model) <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
</div>

@once
    <script>
        (function () {
            function closestPicker(el) {
                return el.closest('[data-channel-picker]');
            }

            function filterOptions(picker, query) {
                const needle = query.trim().toLowerCase();
                picker.querySelectorAll('[data-channel-picker-option]').forEach((option) => {
                    const matches = needle === '' || option.dataset.name.toLowerCase().includes(needle);
                    option.classList.toggle('hidden', !matches);
                });
            }

            function selectOption(picker, option) {
                const search = picker.querySelector('[data-channel-picker-search]');
                const hidden = picker.querySelector('[data-channel-picker-value]');

                search.value = '#' + option.dataset.name;
                hidden.value = option.dataset.id;
                hidden.dispatchEvent(new Event('input', { bubbles: true }));

                picker.querySelector('[data-channel-picker-list]').classList.add('hidden');
            }

            // Delegated on document (not bound per-element) so the picker
            // keeps working after Livewire morphs the DOM on any wire:model.live
            // update elsewhere in the component - no re-init step needed.
            document.addEventListener('input', (event) => {
                if (!event.target.matches('[data-channel-picker-search]')) return;
                const picker = closestPicker(event.target);
                filterOptions(picker, event.target.value);
                picker.querySelector('[data-channel-picker-list]').classList.remove('hidden');
            });

            document.addEventListener('focusin', (event) => {
                if (!event.target.matches('[data-channel-picker-search]')) return;
                const picker = closestPicker(event.target);
                filterOptions(picker, event.target.value);
                picker.querySelector('[data-channel-picker-list]').classList.remove('hidden');
            });

            // mousedown + preventDefault (not click) so the option's selection
            // fires before the search input's blur/focusin-elsewhere would
            // otherwise hide the list first.
            document.addEventListener('mousedown', (event) => {
                const option = event.target.closest('[data-channel-picker-option]');
                if (option) {
                    event.preventDefault();
                    selectOption(closestPicker(option), option);
                    return;
                }

                if (!closestPicker(event.target)) {
                    document.querySelectorAll('[data-channel-picker-list]').forEach((list) => list.classList.add('hidden'));
                }
            });
        })();
    </script>
@endonce
