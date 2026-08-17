@php
    $fieldCurrentUrl ??= null;
@endphp

<div
    x-data="{ uploadError: false }"
    x-on:livewire-upload-start.window="if ($event.detail.property === '{{ $fieldModel }}') uploadError = false"
    x-on:livewire-upload-error.window="if ($event.detail.property === '{{ $fieldModel }}') uploadError = true"
>
    <label class="block text-sm text-muted mb-1">{{ $fieldLabel }}</label>
    <input type="file" wire:model="{{ $fieldModel }}" accept="image/*" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">

    <p x-show="uploadError" class="text-danger text-xs mt-1" style="display: none;">
        Upload failed. The file may be too large (max 5MB) or an unsupported format &mdash; try a smaller JPG or PNG.
    </p>

    @error($fieldModel) <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror

    @if ($fieldFile && $fieldFile->isPreviewable())
        <img src="{{ $fieldFile->temporaryUrl() }}" alt="Preview" class="mt-2 h-24 rounded-control object-cover">
    @elseif ($fieldCurrentUrl)
        <img src="{{ $fieldCurrentUrl }}" alt="Current image" class="mt-2 h-24 rounded-control object-cover">
    @endif
</div>
