<?php

declare(strict_types=1);

namespace App\Livewire\StandardGiveaways;

use App\Actions\StandardGiveaways\UpdateStandardGiveawayStatusAction;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class StandardGiveawayIndex extends Component
{
    public Guild $guild;

    public bool $showCreateForm = false;

    public ?int $selectedId = null;

    public bool $editingSeries = false;

    public function mount(Guild $guild): void
    {
        $this->authorize('view', $guild);

        $this->guild = $guild;
    }

    #[On('standard-giveaway-created')]
    public function closeCreateForm(): void
    {
        $this->showCreateForm = false;
    }

    #[On('standard-giveaway-updated')]
    public function closeEditForm(): void
    {
        $this->editingSeries = false;
    }

    public function setStatus(int $giveawayId, string $status, UpdateStandardGiveawayStatusAction $updateStatus): void
    {
        $giveaway = StandardGiveaway::query()->where('guild_id', $this->guild->id)->findOrFail($giveawayId);

        $this->authorize('manage', $giveaway);

        $updateStatus->execute($giveaway, $status);
    }

    public function select(int $giveawayId): void
    {
        $exists = StandardGiveaway::query()->where('guild_id', $this->guild->id)->where('id', $giveawayId)->exists();

        $this->selectedId = $exists ? $giveawayId : null;
        $this->editingSeries = false;
    }

    public function deselect(): void
    {
        $this->selectedId = null;
        $this->editingSeries = false;
    }

    public function toggleEditSeries(): void
    {
        $giveaway = StandardGiveaway::query()->where('guild_id', $this->guild->id)->findOrFail($this->selectedId);

        $this->authorize('manage', $giveaway);

        $this->editingSeries = ! $this->editingSeries;
    }

    public function render(): View
    {
        $giveaways = $this->guild->standardGiveaways()
            ->withCount('prizeItems')
            ->with('creator')
            ->orderByDesc('created_at')
            ->get();

        // The detail panel is occurrence-scoped (OccurrenceDashboard), while
        // this list shows series - a series can have zero occurrences yet
        // (a just-created recurring series generates its first one on the
        // next scheduled tick, not synchronously), so this is nullable even
        // when a series is selected.
        $selectedOccurrence = $this->selectedId
            ? StandardGiveawayOccurrence::query()
                ->where('standard_giveaway_id', $this->selectedId)
                ->orderByDesc('scheduled_post_at')
                ->first()
            : null;

        return view('livewire.standard-giveaways.standard-giveaway-index', [
            'giveaways' => $giveaways,
            'selectedGiveaway' => $this->selectedId ? $giveaways->firstWhere('id', $this->selectedId) : null,
            'selectedOccurrence' => $selectedOccurrence,
        ]);
    }
}
