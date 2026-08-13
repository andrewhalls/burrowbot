<?php

declare(strict_types=1);

namespace App\Livewire\Guilds;

use App\Models\Guild;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Guild-level defaults (currently just the default giveaway channel).
 * See openspec specs/guild-management - "Guild settings".
 */
class GuildSettings extends Component
{
    public Guild $guild;

    public string $defaultChannelId = '';

    public function mount(Guild $guild): void
    {
        $this->authorize('manage', $guild);

        $this->guild = $guild;
        $this->defaultChannelId = (string) ($guild->default_channel_id ?? '');
    }

    public function save(): void
    {
        $this->authorize('manage', $this->guild);

        $this->validate(['defaultChannelId' => ['nullable', 'string']]);

        $this->guild->update(['default_channel_id' => $this->defaultChannelId ?: null]);

        $this->dispatch('guild-settings-saved');
    }

    public function render(): View
    {
        return view('livewire.guilds.guild-settings');
    }
}
