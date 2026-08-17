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

/**
 * Edits a single scheduled occurrence's description and prize items,
 * independent of its series and every other occurrence - reuses
 * EditStandardGiveaway's prize-item search/chip pattern, scoped down to
 * just these two fields per the request (design.md Decision 1,
 * add-standard-giveaway-occurrence-editing).
 */
class EditStandardGiveawayOccurrence extends Component
{
    public StandardGiveawayOccurrence $occurrence;

    public Guild $guild;

    public string $description = '';

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

    public function save(UpdateStandardGiveawayOccurrenceAction $updateOccurrence): void
    {
        $this->authorize('manage', $this->occurrence->standardGiveaway);

        $this->validate([
            'description' => ['required', 'string'],
        ]);

        if ($this->selectedPrizeItemIds === []) {
            $this->addError('selectedPrizeItemIds', 'Select at least one prize item.');

            return;
        }

        try {
            $this->occurrence = $updateOccurrence->execute($this->occurrence, [
                'description' => $this->description,
                'prize_item_ids' => $this->selectedPrizeItemIds,
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
