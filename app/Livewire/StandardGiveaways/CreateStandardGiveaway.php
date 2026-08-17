<?php

declare(strict_types=1);

namespace App\Livewire\StandardGiveaways;

use App\Actions\StandardGiveaways\CreateStandardGiveawayAction;
use App\Livewire\Concerns\ResolvesBrowserTimezone;
use App\Livewire\Concerns\SearchesDiscordRoles;
use App\Models\CollectionThemeItem;
use App\Models\DiscordRole;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Support\Events\BuildRecurrenceRule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateStandardGiveaway extends Component
{
    use ResolvesBrowserTimezone;
    use SearchesDiscordRoles;
    use WithFileUploads;

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

    public int $durationMinutes = 10080; // one week

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

    public function mount(Guild $guild): void
    {
        $this->authorize('manage', $guild);

        $this->guild = $guild;
        $this->channelId = (string) ($guild->default_channel_id ?? '');
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

    /**
     * Keyed by id, for turning $selectedPrizeItemIds into chips showing the
     * item's thumbnail and name instead of a bare id in the view.
     *
     * @return \Illuminate\Support\Collection<int, CollectionThemeItem>
     */
    public function getSelectedPrizeItemModelsProperty()
    {
        return CollectionThemeItem::query()
            ->whereIn('id', $this->selectedPrizeItemIds)
            ->get()
            ->keyBy('id');
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
     * Keyed by discord_role_id, for turning $selectedRoleIds into
     * human-readable chips in the view.
     *
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

    public function save(CreateStandardGiveawayAction $createGiveaway, BuildRecurrenceRule $buildRecurrenceRule): void
    {
        $this->authorize('manage', $this->guild);

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

        // Deliberately NOT ->utc() here: $startAt/$recurrenceEndDate carry the
        // admin's local wall-clock numbers (e.g. "20:00"), paired with the
        // separately-stored recurrence_timezone below - ExpandRecurrenceRule
        // passes that timezone straight to recurr alongside these same
        // wall-clock numbers to correctly regenerate future occurrences at
        // the same local time (DST included). Converting to UTC here would
        // silently shift every future occurrence by the timezone offset.
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

        $imagePath = $this->image?->store('standard-giveaway-images', 'public');
        $bannerImagePath = $this->bannerImage?->store('standard-giveaway-images', 'public');

        try {
            $giveaway = $createGiveaway->execute(
                $this->guild,
                $validated['title'],
                $validated['description'],
                $validated['channelId'],
                $validated['postingMode'],
                $validated['winnerCount'],
                $this->requiresBooster,
                $validated['durationMinutes'],
                $this->selectedPrizeItemIds,
                $this->selectedRoleIds,
                $recurrenceRule,
                $startAt,
                $this->resolvedTimezone(),
                $imagePath,
                Auth::user(),
                $bannerImagePath,
                $validated['claimLink'] !== '' ? $validated['claimLink'] : null,
                $validated['claimDeadlineHours'],
                $validated['congratsMessageTemplate'] !== '' ? $validated['congratsMessageTemplate'] : null,
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('selectedPrizeItemIds', $e->getMessage());

            return;
        }

        $this->dispatch('standard-giveaway-created', giveawayId: $giveaway->id);
    }

    public function render(): View
    {
        return view('livewire.standard-giveaways.create-standard-giveaway');
    }
}
