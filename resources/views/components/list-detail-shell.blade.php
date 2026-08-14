@props(['selected' => false])

{{--
    A single render of the list slot and a single render of the detail
    slot, purely CSS-driven (design.md Decision 1, revised during
    implementation): on narrow screens only one of the two columns is
    visible at a time (whichever `hidden` doesn't apply), on `lg:` and up
    both are always visible side by side via the grid. This avoids an
    earlier design that rendered the list slot twice (once per breakpoint
    block) - a single render sidesteps any risk of duplicate wire:key
    values within one Livewire component's DOM tree.
--}}
<div class="grid lg:grid-cols-[480px_1fr] lg:gap-6 lg:items-start">
    <div @class(['hidden lg:block' => $selected])>
        {{ $list }}
    </div>
    <div @class(['hidden lg:block' => ! $selected])>
        @if ($selected)
            <button type="button" wire:click="deselect" class="lg:hidden mb-3 text-sm text-muted hover:text-ink">&larr; Back to list</button>
            {{ $detail }}
        @else
            <x-list-detail-empty />
        @endif
    </div>
</div>
