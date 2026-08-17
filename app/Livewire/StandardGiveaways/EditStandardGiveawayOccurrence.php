<?php

declare(strict_types=1);

namespace App\Livewire\StandardGiveaways;

use App\Actions\StandardGiveaways\UpdateStandardGiveawayOccurrenceAction;
use App\Models\CollectionThemeItem;
use App\Models\Guild;
use App\Models\StandardGiveawayOccurrence;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Edits a single scheduled occurrence's description, prize items, and
 * image, independent of its series and every other occurrence - reuses
 * EditStandardGiveaway's prize-item search/chip pattern, scoped down to
 * just these fields per the request (design.md Decision 1,
 * add-standard-giveaway-occurrence-editing; image added in
 * fix-occurrence-posting-timing).
 */
class EditStandardGiveawayOccurrence extends Component
{
    use WithFileUploads;

    public StandardGiveawayOccurrence $occurrence;

    public Guild $guild;

    public string $description = '';

    public mixed $image = null;

    public string $prizeItemSearch = '';

    /** @var list<int> */
    public array $selectedPrizeItemIds = [];

    public function mount(StandardGiveawayOccurrence $occurrence): void
    {
        $this->authorize('manage', $occurrence->standardGiveaway);

        $this->occurrence = $occurrence;
        $this->guild = $occurrence->standardGiveaway->guild;
        $this->description = $occurrence->description;
        $this->selectedPrizeItemIds = $occurrence->prize_item_ids;
    }

    /**
     * @return \Illuminate\Support\Collection<int, CollectionThemeItem>
     */
    public function getSearchResultsProperty()
    {
        if ($this->prizeItemSearch === '') {
            return collect();
        }

        return CollectionThemeItem::query()
            ->whereHas('collectionTheme', fn ($query) => $query->where('guild_id', $this->guild->id))
            ->whereLike('name', "%{$this->prizeItemSearch}%")
            ->whereNotIn('id', $this->selectedPrizeItemIds)
            ->with('collectionTheme')
            ->limit(10)
            ->get();
    }

    public function addPrizeItem(int $itemId): void
    {
        if (! in_array($itemId, $this->selectedPrizeItemIds, true)) {
            $this->selectedPrizeItemIds[] = $itemId;
        }
    }

    public function removePrizeItem(int $itemId): void
    {
        $this->selectedPrizeItemIds = array_values(array_diff($this->selectedPrizeItemIds, [$itemId]));
    }

    /**
     * Keyed by id, for turning $selectedPrizeItemIds into chips showing the
     * item's thumbnail and name instead of a bare id in the view.
     *
     * @return \Illuminate\Support\Collection<int, CollectionThemeItem>
     */
    public function getSelectedPrizeItemModelsProperty()
    {
        return CollectionThemeItem::query()
            ->whereIn('id', $this->selectedPrizeItemIds)
            ->get()
            ->keyBy('id');
    }

    public function save(UpdateStandardGiveawayOccurrenceAction $updateOccurrence): void
    {
        $this->authorize('manage', $this->occurrence->standardGiveaway);

        $this->validate([
            'description' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($this->selectedPrizeItemIds === []) {
            $this->addError('selectedPrizeItemIds', 'Select at least one prize item.');

            return;
        }

        $imagePath = $this->image?->store('standard-giveaway-images', 'public') ?? $this->occurrence->image_path;

        try {
            $this->occurrence = $updateOccurrence->execute($this->occurrence, [
                'description' => $this->description,
                'prize_item_ids' => $this->selectedPrizeItemIds,
                'image_path' => $imagePath,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->addError('description', $e->getMessage());

            return;
        }

        $this->dispatch('standard-giveaway-occurrence-updated', occurrenceId: $this->occurrence->id);
    }

    public function render(): View
    {
        return view('livewire.standard-giveaways.edit-standard-giveaway-occurrence');
    }
}
