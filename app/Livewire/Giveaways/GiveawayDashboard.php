<?php

declare(strict_types=1);

namespace App\Livewire\Giveaways;

use App\Actions\Giveaways\FulfillGiveawayEntryAction;
use App\Actions\Giveaways\StartGiveawayAction;
use App\Models\Giveaway;
use App\Models\GiveawayEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Staff screen for one giveaway: search/filter entrants and mark prizes
 * fulfilled. See openspec specs/giveaway-admin-dashboard.
 */
class GiveawayDashboard extends Component
{
    use WithPagination;

    public Giveaway $giveaway;

    #[Url]
    public string $search = '';

    #[Url]
    public string $itemFilter = '';

    #[Url]
    public string $fulfilmentFilter = 'all';

    public function mount(Giveaway $giveaway): void
    {
        $this->authorize('view', $giveaway);

        $this->giveaway = $giveaway;
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    public function markFulfilled(int $entryId, FulfillGiveawayEntryAction $fulfillEntry): void
    {
        $this->authorize('manage', $this->giveaway);

        $entry = GiveawayEntry::query()
            ->where('giveaway_id', $this->giveaway->id)
            ->findOrFail($entryId);

        $fulfillEntry->execute($entry, Auth::user());
    }

    public function start(StartGiveawayAction $startGiveaway): void
    {
        $this->authorize('manage', $this->giveaway);

        $this->giveaway = $startGiveaway->execute($this->giveaway);
    }

    public function render(): View
    {
        $entries = GiveawayEntry::query()
            ->where('giveaway_id', $this->giveaway->id)
            ->with(['discordMember', 'collectionThemeItem', 'fulfilledBy'])
            ->when($this->search !== '', function (Builder $query) {
                $query->whereHas('discordMember', function (Builder $memberQuery) {
                    $memberQuery->search($this->search);
                });
            })
            ->when($this->itemFilter !== '', fn (Builder $query) => $query->where('collection_theme_item_id', $this->itemFilter))
            ->when($this->fulfilmentFilter === 'fulfilled', fn (Builder $query) => $query->whereNotNull('fulfilled_at'))
            ->when($this->fulfilmentFilter === 'unfulfilled', fn (Builder $query) => $query->whereNull('fulfilled_at'))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.giveaways.giveaway-dashboard', [
            'entries' => $entries,
            'items' => $this->giveaway->collectionTheme->items,
        ]);
    }
}
