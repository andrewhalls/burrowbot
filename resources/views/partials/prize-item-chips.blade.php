<ul class="flex flex-wrap gap-2">
    @foreach ($selectedPrizeItemIds as $itemId)
        @php($item = $this->selectedPrizeItemModels[$itemId] ?? null)
        <li wire:key="selected-item-{{ $itemId }}" class="inline-flex items-center gap-1.5 rounded-full bg-surface-hover pl-1 pr-3 py-1 text-xs">
            @if ($item?->image_url)
                <img src="{{ $item->image_url }}" alt="" class="h-5 w-5 rounded-full object-cover">
            @endif
            {{ $item->name ?? "#{$itemId}" }}
            <button type="button" wire:click="removePrizeItem({{ $itemId }})" class="text-muted hover:text-danger">&times;</button>
        </li>
    @endforeach
</ul>
