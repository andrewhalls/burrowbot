<?php

declare(strict_types=1);

namespace App\Livewire\Giveaways;

use App\Actions\Giveaways\CreateGiveawayAction;
use App\Livewire\Concerns\ResolvesBrowserTimezone;
use App\Models\CollectionTheme;
use App\Models\Guild;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateGiveaway extends Component
{
    use ResolvesBrowserTimezone;
    use WithFileUploads;

    public Guild $guild;

    public string $channelId = '';

    public ?int $collectionThemeId = null;

    public ?int $durationMinutes = null;

    public string $scheduledStartDate = '';

    public string $scheduledStartTime = '';

    public string $description = '';

    public mixed $image = null;

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
            'scheduledStartDate' => ['nullable', 'required_with:scheduledStartTime', 'date'],
            'scheduledStartTime' => ['nullable', 'required_with:scheduledStartDate', 'date_format:H:i'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $theme = CollectionTheme::query()->findOrFail($validated['collectionThemeId']);

        $scheduledStartAt = null;
        if ($this->scheduledStartDate !== '' && $this->scheduledStartTime !== '') {
            $scheduledStartAt = Carbon::parse("{$this->scheduledStartDate} {$this->scheduledStartTime}", $this->resolvedTimezone())->utc();

            if ($scheduledStartAt->isPast()) {
                $this->addError('scheduledStartDate', 'The scheduled start must be in the future.');

                return;
            }
        }

        $imagePath = $this->image?->store('giveaway-images', 'public');

        $giveaway = $createGiveaway->execute(
            $this->guild,
            $theme,
            $validated['channelId'],
            $validated['durationMinutes'],
            $scheduledStartAt,
            $validated['description'] !== '' ? $validated['description'] : null,
            $imagePath,
            Auth::user(),
        );

        $this->dispatch('giveaway-created', giveawayId: $giveaway->id);

        $this->reset(['collectionThemeId', 'durationMinutes', 'scheduledStartDate', 'scheduledStartTime', 'description', 'image']);
    }

    public function render(): View
    {
        return view('livewire.giveaways.create-giveaway', [
            'themes' => $this->guild->collectionThemes()->orderBy('name')->get(),
        ]);
    }
}
