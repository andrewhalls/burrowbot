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
