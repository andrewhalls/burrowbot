<?php

declare(strict_types=1);

namespace App\Livewire\Giveaways;

use App\Actions\Giveaways\StartGiveawayAction;
use App\Models\Giveaway;
use App\Models\Guild;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Guild-scoped list of popup giveaways - status, entrant count, and a
 * Start action for drafts. See openspec specs/giveaway-admin-dashboard -
 * "Giveaway list view".
 */
class GiveawayIndex extends Component
{
    public Guild $guild;

    public bool $showCreateForm = false;

    public function mount(Guild $guild): void
    {
        $this->authorize('view', $guild);

        $this->guild = $guild;
    }

    #[On('giveaway-created')]
    public function closeCreateForm(): void
    {
        $this->showCreateForm = false;
    }

    public function start(int $giveawayId, StartGiveawayAction $startGiveaway): void
    {
        $giveaway = Giveaway::query()->where('guild_id', $this->guild->id)->findOrFail($giveawayId);

        $this->authorize('manage', $giveaway);

        $startGiveaway->execute($giveaway);
    }

    public function render(): View
    {
        $giveaways = $this->guild->giveaways()
            ->withCount('entries')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.giveaways.giveaway-index', [
            'giveaways' => $giveaways,
        ]);
    }
}
