<?php

declare(strict_types=1);

namespace App\Livewire\Giveaways;

use App\Actions\Giveaways\UpdateGiveawayWinnerMessageAction;
use App\Models\Giveaway;
use App\Models\Guild;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Edits a giveaway's winner-message channel/template independent of the
 * main draft-only edit form - reachable at any giveaway status (draft,
 * active, or closed) since these two fields only affect future win
 * events, never the already-posted Discord message.
 *
 * See openspec specs/giveaway-lifecycle - "Winner-message configuration
 * stays editable regardless of giveaway status".
 */
class EditGiveawayWinnerMessage extends Component
{
    public Giveaway $giveaway;

    public Guild $guild;

    public string $winnerMessageChannelId = '';

    public string $winnerMessageTemplate = '';

    public function mount(Giveaway $giveaway): void
    {
        $this->authorize('manage', $giveaway);

        $this->guild = $giveaway->guild;

        abort_unless($this->guild->popup_giveaway_winner_messages_enabled, 403);

        $this->giveaway = $giveaway;
        $this->winnerMessageChannelId = (string) $giveaway->winner_message_channel_id;
        $this->winnerMessageTemplate = (string) $giveaway->winner_message_template;
    }

    public function save(UpdateGiveawayWinnerMessageAction $updateWinnerMessage): void
    {
        $this->authorize('manage', $this->giveaway);

        $validated = $this->validate([
            'winnerMessageChannelId' => ['nullable', 'string', 'required_with:winnerMessageTemplate'],
            'winnerMessageTemplate' => ['nullable', 'string', 'required_with:winnerMessageChannelId'],
        ]);

        $this->giveaway = $updateWinnerMessage->execute(
            $this->giveaway,
            $validated['winnerMessageChannelId'] !== '' ? $validated['winnerMessageChannelId'] : null,
            $validated['winnerMessageTemplate'] !== '' ? $validated['winnerMessageTemplate'] : null,
        );

        $this->dispatch('giveaway-winner-message-updated', giveawayId: $this->giveaway->id);
    }

    public function render(): View
    {
        return view('livewire.giveaways.edit-giveaway-winner-message');
    }
}
