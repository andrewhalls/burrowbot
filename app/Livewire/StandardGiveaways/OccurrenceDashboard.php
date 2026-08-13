<?php

declare(strict_types=1);

namespace App\Livewire\StandardGiveaways;

use App\Models\StandardGiveawayEntry;
use App\Models\StandardGiveawayOccurrence;
use App\Models\StandardGiveawayWinner;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

/**
 * Staff screen for one standard giveaway occurrence: entrant list and
 * drawn-winners list, searchable by member. Mirrors the Events feature's
 * OccurrenceRoster and the giveaway platform's GiveawayDashboard.
 */
class OccurrenceDashboard extends Component
{
    public StandardGiveawayOccurrence $occurrence;

    public string $search = '';

    public function mount(StandardGiveawayOccurrence $occurrence): void
    {
        $this->authorize('view', $occurrence->standardGiveaway);

        $this->occurrence = $occurrence;
    }

    public function render(): View
    {
        $entries = StandardGiveawayEntry::query()
            ->where('standard_giveaway_occurrence_id', $this->occurrence->id)
            ->with('discordMember')
            ->when($this->search !== '', function (Builder $query) {
                $query->whereHas('discordMember', function (Builder $memberQuery) {
                    $memberQuery->search($this->search);
                });
            })
            ->get();

        $winners = StandardGiveawayWinner::query()
            ->where('standard_giveaway_occurrence_id', $this->occurrence->id)
            ->with(['standardGiveawayEntry.discordMember', 'collectionThemeItem'])
            ->when($this->search !== '', function (Builder $query) {
                $query->whereHas('standardGiveawayEntry.discordMember', function (Builder $memberQuery) {
                    $memberQuery->search($this->search);
                });
            })
            ->get();

        return view('livewire.standard-giveaways.occurrence-dashboard', [
            'entries' => $entries,
            'winners' => $winners,
        ]);
    }
}
