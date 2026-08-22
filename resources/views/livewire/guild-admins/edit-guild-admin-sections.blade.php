<div class="rounded-card border border-line p-4 space-y-4 max-w-xl">
    <div class="space-y-1">
        @foreach ($sectionLabels as $key => $label)
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model="sections" value="{{ $key }}">
                {{ $label }}
            </label>
        @endforeach
    </div>
    @error('sections') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror

    <button type="button" wire:click="save" class="rounded-control bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
        Save changes
    </button>
</div>
