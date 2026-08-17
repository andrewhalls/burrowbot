<?php

declare(strict_types=1);

namespace App\Livewire\Giveaways;

use App\Actions\Giveaways\DeleteGiveawayDraftAction;
use App\Actions\Giveaways\StartGiveawayAction;
use App\Models\Giveaway;
use App\Models\Guild;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Guild-scoped list of popup giveaways - status, entrant count, and a
 * Start action for drafts. Selecting a giveaway opens its entrant/fulfilment
 * dashboard in a side panel rather than navigating away. See openspec
 * specs/giveaway-admin-dashboard - "Giveaway list view";
 * specs/dashboard-list-detail-layout.
 */
class GiveawayIndex extends Component
{
    public Guild $guild;

    public bool $showCreateForm = false;

    public ?int $selectedId = null;

    public bool $editing = false;

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
            $this->editing = false;
        }
    }

    #[On('giveaway-created')]
    public function closeCreateForm(int $giveawayId): void
    {
        $this->showCreateForm = false;
        $this->selectedId = $giveawayId;
    }

    #[On('giveaway-updated')]
    public function closeEditForm(): void
    {
        $this->editing = false;
    }

    public function select(int $giveawayId): void
    {
        $exists = Giveaway::query()->where('guild_id', $this->guild->id)->where('id', $giveawayId)->exists();

        $this->selectedId = $exists ? $giveawayId : null;
        $this->showCreateForm = false;
        $this->editing = false;
    }

    public function deselect(): void
    {
        $this->selectedId = null;
        $this->editing = false;
    }

    public function toggleEdit(): void
    {
        $giveaway = Giveaway::query()->where('guild_id', $this->guild->id)->findOrFail($this->selectedId);

        $this->authorize('manage', $giveaway);

        $this->editing = ! $this->editing;
    }

    public function start(int $giveawayId, StartGiveawayAction $startGiveaway): void
    {
        $giveaway = Giveaway::query()->where('guild_id', $this->guild->id)->findOrFail($giveawayId);

        $this->authorize('manage', $giveaway);

        $startGiveaway->execute($giveaway);
    }

    public function delete(DeleteGiveawayDraftAction $deleteGiveaway): void
    {
        $giveaway = Giveaway::query()->where('guild_id', $this->guild->id)->findOrFail($this->selectedId);

        $this->authorize('manage', $giveaway);

        try {
            $deleteGiveaway->execute($giveaway);
        } catch (InvalidArgumentException $e) {
            $this->addError('delete', $e->getMessage());

            return;
        }

        $this->selectedId = null;
        $this->editing = false;
    }

    public function render(): View
    {
        $giveaways = $this->guild->giveaways()
            ->withCount('entries')
            ->with('creator')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.giveaways.giveaway-index', [
            'giveaways' => $giveaways,
            'selectedGiveaway' => $this->selectedId ? $giveaways->firstWhere('id', $this->selectedId) : null,
        ]);
    }
}
