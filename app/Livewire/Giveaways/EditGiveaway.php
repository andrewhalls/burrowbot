<?php

declare(strict_types=1);

namespace App\Livewire\Giveaways;

use App\Actions\Giveaways\UpdateGiveawayDraftAction;
use App\Livewire\Concerns\ResolvesBrowserTimezone;
use App\Models\CollectionTheme;
use App\Models\Giveaway;
use App\Models\Guild;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Full-parity edit form for a still-draft popup giveaway - mirrors
 * CreateGiveaway's fields/validation but pre-fills from the existing
 * giveaway and saves via UpdateGiveawayDraftAction (design.md Decision 1).
 */
class EditGiveaway extends Component
{
    use ResolvesBrowserTimezone;
    use WithFileUploads;

    public Giveaway $giveaway;

    public Guild $guild;

    public string $channelId = '';

    public ?int $collectionThemeId = null;

    public ?int $durationMinutes = null;

    public string $scheduledStartDate = '';

    public string $scheduledStartTime = '';

    public string $description = '';

    public mixed $image = null;

    public function mount(Giveaway $giveaway): void
    {
        $this->authorize('manage', $giveaway);

        $this->giveaway = $giveaway;
        $this->guild = $giveaway->guild;
        $this->channelId = $giveaway->channel_id;
        $this->collectionThemeId = $giveaway->collection_theme_id;
        $this->durationMinutes = $giveaway->duration_minutes;
        $this->description = (string) $giveaway->description;

        if ($giveaway->scheduled_start_at) {
            $local = $giveaway->scheduled_start_at->clone()->setTimezone($this->resolvedTimezone());
            $this->scheduledStartDate = $local->toDateString();
            $this->scheduledStartTime = $local->format('H:i');
        }
    }

    public function save(UpdateGiveawayDraftAction $updateGiveaway): void
    {
        $this->authorize('manage', $this->giveaway);

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

        $imagePath = $this->image?->store('giveaway-images', 'public') ?? $this->giveaway->image_path;

        try {
            $this->giveaway = $updateGiveaway->execute($this->giveaway, [
                'collection_theme_id' => $theme->id,
                'channel_id' => $validated['channelId'],
                'duration_minutes' => $validated['durationMinutes'],
                'scheduled_start_at' => $scheduledStartAt,
                'description' => $validated['description'] !== '' ? $validated['description'] : null,
                'image_path' => $imagePath,
            ]);
        } catch (InvalidArgumentException $e) {
            $this->addError('collectionThemeId', $e->getMessage());

            return;
        }

        $this->dispatch('giveaway-updated', giveawayId: $this->giveaway->id);
    }

    public function render(): View
    {
        return view('livewire.giveaways.edit-giveaway', [
            'themes' => $this->guild->collectionThemes()->orderBy('name')->get(),
        ]);
    }
}
