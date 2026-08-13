<?php

declare(strict_types=1);

namespace App\Livewire\StandardGiveaways;

use App\Actions\StandardGiveaways\UpdateStandardGiveawayStatusAction;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class StandardGiveawayIndex extends Component
{
    public Guild $guild;

    public bool $showCreateForm = false;

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

    public function setStatus(int $giveawayId, string $status, UpdateStandardGiveawayStatusAction $updateStatus): void
    {
        $giveaway = StandardGiveaway::query()->where('guild_id', $this->guild->id)->findOrFail($giveawayId);

        $this->authorize('manage', $giveaway);

        $updateStatus->execute($giveaway, $status);
    }

    public function render(): View
    {
        $giveaways = $this->guild->standardGiveaways()
            ->withCount('prizeItems')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.standard-giveaways.standard-giveaway-index', [
            'giveaways' => $giveaways,
        ]);
    }
}
