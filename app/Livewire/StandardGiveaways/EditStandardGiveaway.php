<?php

declare(strict_types=1);

namespace App\Livewire\StandardGiveaways;

use App\Actions\StandardGiveaways\UpdateStandardGiveawayAction;
use App\Livewire\Concerns\ResolvesBrowserTimezone;
use App\Livewire\Concerns\SearchesDiscordRoles;
use App\Models\CollectionThemeItem;
use App\Models\DiscordRole;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Support\Events\BuildRecurrenceRule;
use App\Support\Events\ParseRecurrenceRule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Full-parity edit form for a standard giveaway series - mirrors
 * CreateStandardGiveaway's fields/validation but pre-fills from the
 * existing series and saves via UpdateStandardGiveawayAction (design.md
 * Decision 1). Editing only affects occurrences generated after the save;
 * already-generated occurrences keep their snapshotted values.
 */
class EditStandardGiveaway extends Component
{
    use ResolvesBrowserTimezone;
    use SearchesDiscordRoles;
    use WithFileUploads;

    public StandardGiveaway $giveaway;

    public Guild $guild;

    public string $title = '';

    public string $description = '';

    public mixed $image = null;

    public mixed $bannerImage = null;

    public string $claimLink = '';

    public ?int $claimDeadlineHours = null;

    public string $congratsMessageTemplate = '';

    public string $channelId = '';

    public string $postingMode = StandardGiveaway::POSTING_MODE_MESSAGE;

    public int $winnerCount = 1;

    public bool $requiresBooster = false;

    /** @var list<string> */
    public array $selectedRoleIds = [];

    public int $durationMinutes = 10080;

    public string $prizeItemSearch = '';

    /** @var list<int> */
    public array $selectedPrizeItemIds = [];

    public string $startDate = '';

    public string $startTime = '';

    public string $recurrenceType = 'none';

    public int $recurrenceInterval = 1;

    /** @var list<string> */
    public array $recurrenceDaysOfWeek = [];

    public string $recurrenceEndType = 'never';

    public string $recurrenceEndDate = '';

    public ?int $recurrenceEndCount = null;

    public function mount(StandardGiveaway $giveaway, ParseRecurrenceRule $parseRecurrenceRule): void
    {
        $this->authorize('manage', $giveaway);

        $giveaway->load('prizeItems', 'requiredRoles');

        $this->giveaway = $giveaway;
        $this->guild = $giveaway->guild;
        $this->title = $giveaway->title;
        $this->description = $giveaway->description;
        $this->channelId = $giveaway->channel_id;
        $this->postingMode = $giveaway->posting_mode;
        $this->winnerCount = $giveaway->winner_count;
        $this->requiresBooster = $giveaway->requires_booster;
        $this->durationMinutes = $giveaway->duration_minutes;
        $this->claimLink = $giveaway->claim_link ?? '';
        $this->claimDeadlineHours = $giveaway->claim_deadline_hours;
        $this->congratsMessageTemplate = $giveaway->congrats_message_template ?? '';
        $this->selectedPrizeItemIds = $giveaway->prizeItems->pluck('collection_theme_item_id')->all();
        $this->selectedRoleIds = $giveaway->requiredRoles->pluck('discord_role_id')->all();

        if ($giveaway->recurrence_start_at) {
            $local = $giveaway->recurrence_start_at->clone()->setTimezone($giveaway->recurrence_timezone ?? 'UTC');
            $this->startDate = $local->toDateString();
            $this->startTime = $local->format('H:i');
        }

        $parsed = $parseRecurrenceRule($giveaway->recurrence_rule);
        $this->recurrenceType = $parsed['type'];
        $this->recurrenceInterval = $parsed['interval'];
        $this->recurrenceDaysOfWeek = $parsed['daysOfWeek'];
        $this->recurrenceEndType = $parsed['endType'];
        $this->recurrenceEndDate = $parsed['endDate']?->toDateString() ?? '';
        $this->recurrenceEndCount = $parsed['endCount'];
    }

    /**
     * @return \Illuminate\Support\Collection<int, CollectionThemeItem>
     */
    public function getSearchResultsProperty()
    {
        if ($this->prizeItemSearch === '') {
            return collect();
        }

        return CollectionThemeItem::query()
            ->whereHas('collectionTheme', fn ($query) => $query->where('guild_id', $this->guild->id))
            ->whereLike('name', "%{$this->prizeItemSearch}%")
            ->whereNotIn('id', $this->selectedPrizeItemIds)
            ->with('collectionTheme')
            ->limit(10)
            ->get();
    }

    public function addPrizeItem(int $itemId): void
    {
        if (! in_array($itemId, $this->selectedPrizeItemIds, true)) {
            $this->selectedPrizeItemIds[] = $itemId;
        }
    }

    public function removePrizeItem(int $itemId): void
    {
        $this->selectedPrizeItemIds = array_values(array_diff($this->selectedPrizeItemIds, [$itemId]));
    }

    public function addDiscordRole(string $discordRoleId): void
    {
        if (! in_array($discordRoleId, $this->selectedRoleIds, true)) {
            $this->selectedRoleIds[] = $discordRoleId;
        }
    }

    public function removeDiscordRole(string $discordRoleId): void
    {
        $this->selectedRoleIds = array_values(array_diff($this->selectedRoleIds, [$discordRoleId]));
    }

    public function addRoleSetPreset(int $roleSetId): void
    {
        $preset = $this->guild->eventRoleSets()->with('roles')->findOrFail($roleSetId);

        foreach ($preset->roles as $role) {
            if ($role->discord_role_id) {
                $this->addDiscordRole($role->discord_role_id);
            }
        }
    }

    /**
     * @return \Illuminate\Support\Collection<string, DiscordRole>
     */
    public function getSelectedRoleModelsProperty()
    {
        return DiscordRole::query()
            ->where('guild_id', $this->guild->id)
            ->whereIn('discord_role_id', $this->selectedRoleIds)
            ->get()
            ->keyBy('discord_role_id');
    }

    protected function guildForRoleSearch(): Guild
    {
        return $this->guild;
    }

    /**
     * @return list<string>
     */
    protected function selectedDiscordRoleIds(): array
    {
        return $this->selectedRoleIds;
    }

    public function save(UpdateStandardGiveawayAction $updateGiveaway, BuildRecurrenceRule $buildRecurrenceRule): void
    {
        $this->authorize('manage', $this->giveaway);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
            'bannerImage' => ['nullable', 'image', 'max:5120'],
            'claimLink' => ['nullable', 'string', 'max:500'],
            'claimDeadlineHours' => ['nullable', 'integer', 'min:1'],
            'congratsMessageTemplate' => ['nullable', 'string'],
            'channelId' => ['required', 'string'],
            'postingMode' => ['required', 'in:'.StandardGiveaway::POSTING_MODE_THREAD.','.StandardGiveaway::POSTING_MODE_MESSAGE],
            'winnerCount' => ['required', 'integer', 'min:1'],
            'durationMinutes' => ['required', 'integer', 'min:1'],
            'startDate' => ['required', 'date'],
            'startTime' => ['required', 'date_format:H:i'],
            'recurrenceType' => ['required', 'in:none,daily,weekly,monthly'],
            'recurrenceInterval' => ['required', 'integer', 'min:1'],
            'recurrenceEndType' => ['required', 'in:never,on_date,after_count'],
        ]);

        if ($this->selectedPrizeItemIds === []) {
            $this->addError('selectedPrizeItemIds', 'Select at least one prize item.');

            return;
        }

        $startAt = Carbon::parse("{$this->startDate} {$this->startTime}", $this->resolvedTimezone());

        try {
            $recurrenceRule = $buildRecurrenceRule(
                $this->recurrenceType,
                $this->recurrenceInterval,
                $this->recurrenceDaysOfWeek,
                $this->recurrenceEndType,
                $this->recurrenceEndDate !== '' ? Carbon::parse($this->recurrenceEndDate, $this->resolvedTimezone()) : null,
                $this->recurrenceEndCount,
                $startAt,
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('recurrenceType', $e->getMessage());

            return;
        }

        $imagePath = $this->image?->store('standard-giveaway-images', 'public') ?? $this->giveaway->image_path;
        $bannerImagePath = $this->bannerImage?->store('standard-giveaway-images', 'public') ?? $this->giveaway->banner_image_path;

        $this->giveaway = $updateGiveaway->execute($this->giveaway, [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'image_path' => $imagePath,
            'banner_image_path' => $bannerImagePath,
            'channel_id' => $validated['channelId'],
            'posting_mode' => $validated['postingMode'],
            'winner_count' => $validated['winnerCount'],
            'requires_booster' => $this->requiresBooster,
            'duration_minutes' => $validated['durationMinutes'],
            'claim_link' => $validated['claimLink'] !== '' ? $validated['claimLink'] : null,
            'claim_deadline_hours' => $validated['claimDeadlineHours'],
            'congrats_message_template' => $validated['congratsMessageTemplate'] !== '' ? $validated['congratsMessageTemplate'] : null,
            'recurrence_rule' => $recurrenceRule,
            'recurrence_start_at' => $startAt,
            'recurrence_timezone' => $this->resolvedTimezone(),
            'prize_item_ids' => $this->selectedPrizeItemIds,
            'required_role_ids' => $this->selectedRoleIds,
        ]);

        $this->dispatch('standard-giveaway-updated', giveawayId: $this->giveaway->id);
    }

    public function render(): View
    {
        return view('livewire.standard-giveaways.edit-standard-giveaway');
    }
}
