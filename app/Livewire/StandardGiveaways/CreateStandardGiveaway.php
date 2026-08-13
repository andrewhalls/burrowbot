<?php

declare(strict_types=1);

namespace App\Livewire\StandardGiveaways;

use App\Actions\StandardGiveaways\CreateStandardGiveawayAction;
use App\Models\CollectionThemeItem;
use App\Models\Guild;
use App\Models\StandardGiveaway;
use App\Support\Events\BuildRecurrenceRule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Livewire\Component;

class CreateStandardGiveaway extends Component
{
    public Guild $guild;

    public string $title = '';

    public string $description = '';

    public string $channelId = '';

    public string $postingMode = StandardGiveaway::POSTING_MODE_MESSAGE;

    public int $winnerCount = 1;

    public bool $requiresBooster = false;

    public string $requiredRoleIdsInput = '';

    public int $durationMinutes = 10080; // one week

    public string $prizeItemSearch = '';

    /** @var list<int> */
    public array $selectedPrizeItemIds = [];

    public string $startDate = '';

    public string $startTime = '';

    public string $timezone;

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
        $this->timezone = config('app.timezone');
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

    public function save(CreateStandardGiveawayAction $createGiveaway, BuildRecurrenceRule $buildRecurrenceRule): void
    {
        $this->authorize('manage', $this->guild);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'channelId' => ['required', 'string'],
            'postingMode' => ['required', 'in:'.StandardGiveaway::POSTING_MODE_THREAD.','.StandardGiveaway::POSTING_MODE_MESSAGE],
            'winnerCount' => ['required', 'integer', 'min:1'],
            'durationMinutes' => ['required', 'integer', 'min:1'],
            'startDate' => ['required', 'date'],
            'startTime' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'timezone'],
            'recurrenceType' => ['required', 'in:none,daily,weekly,monthly'],
            'recurrenceInterval' => ['required', 'integer', 'min:1'],
            'recurrenceEndType' => ['required', 'in:never,on_date,after_count'],
        ]);

        if ($this->selectedPrizeItemIds === []) {
            $this->addError('selectedPrizeItemIds', 'Select at least one prize item.');

            return;
        }

        $startAt = Carbon::parse("{$this->startDate} {$this->startTime}", $this->timezone);

        try {
            $recurrenceRule = $buildRecurrenceRule(
                $this->recurrenceType,
                $this->recurrenceInterval,
                $this->recurrenceDaysOfWeek,
                $this->recurrenceEndType,
                $this->recurrenceEndDate !== '' ? Carbon::parse($this->recurrenceEndDate, $this->timezone) : null,
                $this->recurrenceEndCount,
                $startAt,
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('recurrenceType', $e->getMessage());

            return;
        }

        $requiredRoleIds = collect(preg_split('/[,\s]+/', $this->requiredRoleIdsInput, -1, PREG_SPLIT_NO_EMPTY))
            ->map(fn ($id) => trim($id))
            ->filter()
            ->values()
            ->all();

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
                $requiredRoleIds,
                $recurrenceRule,
                $startAt,
                $this->timezone,
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
