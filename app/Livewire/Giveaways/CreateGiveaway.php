<?php

declare(strict_types=1);

namespace App\Livewire\Giveaways;

use App\Actions\Giveaways\CreateGiveawayAction;
use App\Models\CollectionTheme;
use App\Models\Guild;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreateGiveaway extends Component
{
    public Guild $guild;

    public string $channelId = '';

    public ?int $collectionThemeId = null;

    public ?int $durationMinutes = null;

    public function mount(Guild $guild): void
    {
        $this->authorize('manage', $guild);

        $this->guild = $guild;
        $this->channelId = (string) ($guild->default_channel_id ?? '');
    }

    public function save(CreateGiveawayAction $createGiveaway): void
    {
        $this->authorize('manage', $this->guild);

        $validated = $this->validate([
            'channelId' => ['required', 'string'],
            'collectionThemeId' => [
                'required',
                'integer',
                'exists:collection_themes,id,guild_id,'.$this->guild->id,
            ],
            'durationMinutes' => ['required', 'integer', 'min:1'],
        ]);

        $theme = CollectionTheme::query()->findOrFail($validated['collectionThemeId']);

        $giveaway = $createGiveaway->execute($this->guild, $theme, $validated['channelId'], $validated['durationMinutes']);

        $this->dispatch('giveaway-created', giveawayId: $giveaway->id);

        $this->reset(['collectionThemeId', 'durationMinutes']);
    }

    public function render(): View
    {
        return view('livewire.giveaways.create-giveaway', [
            'themes' => $this->guild->collectionThemes()->orderBy('name')->get(),
        ]);
    }
}
