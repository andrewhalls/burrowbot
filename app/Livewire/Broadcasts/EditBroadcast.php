<?php

declare(strict_types=1);

namespace App\Livewire\Broadcasts;

use App\Actions\Broadcasts\UpdateBroadcastAction;
use App\Livewire\Concerns\ResolvesBrowserTimezone;
use App\Models\Broadcast;
use App\Models\Guild;
use App\Support\Events\BuildRecurrenceRule;
use App\Support\Events\ParseRecurrenceRule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Livewire\Component;

/**
 * Full-parity edit form for a broadcast series - mirrors CreateBroadcast's
 * fields/validation but pre-fills from the existing broadcast and saves
 * via UpdateBroadcastAction. Editing only affects occurrences generated
 * after the save; already-generated occurrences keep their snapshotted
 * values.
 */
class EditBroadcast extends Component
{
    use ResolvesBrowserTimezone;

    public Broadcast $broadcast;

    public Guild $guild;

    public string $title = '';

    public string $messageTemplate = '';

    public string $channelId = '';

    public string $startDate = '';

    public string $startTime = '';

    public string $recurrenceType = 'none';

    public int $recurrenceInterval = 1;

    /** @var list<string> */
    public array $recurrenceDaysOfWeek = [];

    public string $recurrenceEndType = 'never';

    public string $recurrenceEndDate = '';

    public ?int $recurrenceEndCount = null;

    public function mount(Broadcast $broadcast, ParseRecurrenceRule $parseRecurrenceRule): void
    {
        $this->authorize('manage', $broadcast);

        $this->broadcast = $broadcast;
        $this->guild = $broadcast->guild;
        $this->title = $broadcast->title;
        $this->messageTemplate = $broadcast->message_template;
        $this->channelId = $broadcast->channel_id;

        if ($broadcast->recurrence_start_at) {
            $local = $broadcast->recurrence_start_at->clone()->setTimezone($broadcast->recurrence_timezone ?? 'UTC');
            $this->startDate = $local->toDateString();
            $this->startTime = $local->format('H:i');
        }

        $parsed = $parseRecurrenceRule($broadcast->recurrence_rule);
        $this->recurrenceType = $parsed['type'];
        $this->recurrenceInterval = $parsed['interval'];
        $this->recurrenceDaysOfWeek = $parsed['daysOfWeek'];
        $this->recurrenceEndType = $parsed['endType'];
        $this->recurrenceEndDate = $parsed['endDate']?->toDateString() ?? '';
        $this->recurrenceEndCount = $parsed['endCount'];
    }

    public function save(UpdateBroadcastAction $updateBroadcast, BuildRecurrenceRule $buildRecurrenceRule): void
    {
        $this->authorize('manage', $this->broadcast);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'messageTemplate' => ['required', 'string'],
            'channelId' => ['required', 'string'],
            'startDate' => ['required', 'date'],
            'startTime' => ['required', 'date_format:H:i'],
            'recurrenceType' => ['required', 'in:none,daily,weekly,monthly'],
            'recurrenceInterval' => ['required', 'integer', 'min:1'],
            'recurrenceEndType' => ['required', 'in:never,on_date,after_count'],
        ]);

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

        $this->broadcast = $updateBroadcast->execute($this->broadcast, [
            'title' => $validated['title'],
            'message_template' => $validated['messageTemplate'],
            'channel_id' => $validated['channelId'],
            'recurrence_rule' => $recurrenceRule,
            'recurrence_start_at' => $startAt,
            'recurrence_timezone' => $this->resolvedTimezone(),
        ]);

        $this->dispatch('broadcast-updated', broadcastId: $this->broadcast->id);
    }

    public function render(): View
    {
        return view('livewire.broadcasts.edit-broadcast');
    }
}
