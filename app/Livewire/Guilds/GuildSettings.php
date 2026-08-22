<?php

declare(strict_types=1);

namespace App\Livewire\Guilds;

use App\Models\Guild;
use App\Support\GuildAdmins\GuildAdminSection;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Guild-level defaults (currently just the default giveaway channel).
 * Gated on the "settings" section rather than blanket guild admin, since
 * Settings is itself one of the seven grantable sections (design.md
 * Decision 6, add-guild-admin-permissions).
 *
 * See openspec specs/guild-management - "Guild settings".
 */
class GuildSettings extends Component
{
    public Guild $guild;

    public string $defaultChannelId = '';

    public bool $popupGiveawayWinnerMessagesEnabled = true;

    public function mount(Guild $guild): void
    {
        abort_unless(Auth::user()->hasGuildAdminSection($guild, GuildAdminSection::SETTINGS), 403);

        $this->guild = $guild;
        $this->defaultChannelId = (string) ($guild->default_channel_id ?? '');
        $this->popupGiveawayWinnerMessagesEnabled = $guild->popup_giveaway_winner_messages_enabled;
    }

    public function save(): void
    {
        abort_unless(Auth::user()->hasGuildAdminSection($this->guild, GuildAdminSection::SETTINGS), 403);

        $this->validate(['defaultChannelId' => ['nullable', 'string']]);

        $this->guild->update([
            'default_channel_id' => $this->defaultChannelId ?: null,
            'popup_giveaway_winner_messages_enabled' => $this->popupGiveawayWinnerMessagesEnabled,
        ]);

        $this->dispatch('guild-settings-saved');
    }

    public function render(): View
    {
        return view('livewire.guilds.guild-settings');
    }
}
