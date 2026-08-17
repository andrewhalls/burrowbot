<?php

declare(strict_types=1);

namespace App\Livewire\StandardGiveaways;

use App\Actions\StandardGiveaways\ArchiveStandardGiveawayAction;
use App\Actions\StandardGiveaways\DeleteStandardGiveawayAction;
use App\Actions\StandardGiveaways\UnarchiveStandardGiveawayAction;
use App\Actions\StandardGiveaways\UpdateStandardGiveawayStatusAction;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayOccurrence;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Component;

class StandardGiveawayIndex extends Component
{
    public Guild $guild;

    public bool $showCreateForm = false;

    public ?int $selectedId = null;

    public bool $editingSeries = false;

    public ?int $editingOccurrenceId = null;

    public bool $showArchived = false;

    public function mount(Guild $guild): void
    {
        $this->authorize('view', $guild);

        $this->guild = $guild;
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = ! $this->showCreateForm;

        if ($this->showCreateForm) {
            $this->selectedId = null;
            $this->editingSeries = false;
            $this->editingOccurrenceId = null;
        }
    }

    #[On('standard-giveaway-created')]
    public function closeCreateForm(int $giveawayId): void
    {
        $this->showCreateForm = false;
        $this->selectedId = $giveawayId;
    }

    #[On('standard-giveaway-updated')]
    public function closeEditForm(): void
    {
        $this->editingSeries = false;
    }

    #[On('standard-giveaway-occurrence-updated')]
    public function closeEditOccurrenceForm(): void
    {
        $this->editingOccurrenceId = null;
    }

    public function setStatus(int $giveawayId, string $status, UpdateStandardGiveawayStatusAction $updateStatus): void
    {
        $giveaway = StandardGiveaway::query()->where('guild_id', $this->guild->id)->findOrFail($giveawayId);

        $this->authorize('manage', $giveaway);

        $updateStatus->execute($giveaway, $status);
    }

    public function archive(int $giveawayId, ArchiveStandardGiveawayAction $archiveGiveaway): void
    {
        $giveaway = StandardGiveaway::query()->where('guild_id', $this->guild->id)->findOrFail($giveawayId);

        $this->authorize('manage', $giveaway);

        $archiveGiveaway->execute($giveaway);
    }

    public function unarchive(int $giveawayId, UnarchiveStandardGiveawayAction $unarchiveGiveaway): void
    {
        $giveaway = StandardGiveaway::query()->where('guild_id', $this->guild->id)->findOrFail($giveawayId);

        $this->authorize('manage', $giveaway);

        $unarchiveGiveaway->execute($giveaway);
    }

    public function select(int $giveawayId): void
    {
        $exists = StandardGiveaway::query()->where('guild_id', $this->guild->id)->where('id', $giveawayId)->exists();

        $this->selectedId = $exists ? $giveawayId : null;
        $this->editingSeries = false;
        $this->showCreateForm = false;
        $this->editingOccurrenceId = null;
    }

    public function deselect(): void
    {
        $this->selectedId = null;
        $this->editingSeries = false;
        $this->editingOccurrenceId = null;
    }

    public function toggleEditSeries(): void
    {
        $giveaway = StandardGiveaway::query()->where('guild_id', $this->guild->id)->findOrFail($this->selectedId);

        $this->authorize('manage', $giveaway);

        $this->editingSeries = ! $this->editingSeries;
        $this->editingOccurrenceId = null;
    }

    public function toggleEditOccurrence(int $occurrenceId): void
    {
        $giveaway = StandardGiveaway::query()->where('guild_id', $this->guild->id)->findOrFail($this->selectedId);

        $this->authorize('manage', $giveaway);

        if ($this->editingOccurrenceId === $occurrenceId) {
            $this->editingOccurrenceId = null;

            return;
        }

        $occurrence = StandardGiveawayOccurrence::query()
            ->where('standard_giveaway_id', $giveaway->id)
            ->where('status', StandardGiveawayOccurrence::STATUS_SCHEDULED)
            ->findOrFail($occurrenceId);

        $this->editingOccurrenceId = $occurrence->id;
        $this->editingSeries = false;
    }

    public function delete(DeleteStandardGiveawayAction $deleteGiveaway): void
    {
        $giveaway = StandardGiveaway::query()->where('guild_id', $this->guild->id)->findOrFail($this->selectedId);

        $this->authorize('manage', $giveaway);

        try {
            $deleteGiveaway->execute($giveaway);
        } catch (InvalidArgumentException $e) {
            $this->addError('delete', $e->getMessage());

            return;
        }

        $this->selectedId = null;
        $this->editingSeries = false;
        $this->editingOccurrenceId = null;
    }

    public function render(): View
    {
        $giveaways = $this->guild->standardGiveaways()
            ->withCount('prizeItems')
            ->with('creator')
            ->when(! $this->showArchived, fn ($query) => $query->whereNull('archived_at'))
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

        // Upcoming (not-yet-posted) occurrences an admin can edit directly -
        // soonest first, capped like event-summary.blade.php's own "Recent
        // occurrences" list (design.md Decision 3).
        $upcomingOccurrences = $this->selectedId
            ? StandardGiveawayOccurrence::query()
                ->where('standard_giveaway_id', $this->selectedId)
                ->where('status', StandardGiveawayOccurrence::STATUS_SCHEDULED)
                ->orderBy('scheduled_post_at')
                ->limit(10)
                ->get()
            : collect();

        $editingOccurrence = $this->editingOccurrenceId
            ? StandardGiveawayOccurrence::query()
                ->where('standard_giveaway_id', $this->selectedId)
                ->find($this->editingOccurrenceId)
            : null;

        return view('livewire.standard-giveaways.standard-giveaway-index', [
            'giveaways' => $giveaways,
            'selectedGiveaway' => $this->selectedId ? $giveaways->firstWhere('id', $this->selectedId) : null,
            'selectedOccurrence' => $selectedOccurrence,
            'upcomingOccurrences' => $upcomingOccurrences,
            'editingOccurrence' => $editingOccurrence,
        ]);
    }
}
