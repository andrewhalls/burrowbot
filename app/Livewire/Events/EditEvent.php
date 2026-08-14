<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Actions\Events\UpdateEventAction;
use App\Livewire\Concerns\ResolvesBrowserTimezone;
use App\Models\Event;
use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Support\Events\BuildRecurrenceRule;
use App\Support\Events\ParseRecurrenceRule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Full-parity edit form for an event series - mirrors CreateEvent's
 * fields/validation but pre-fills from the existing event and saves via
 * UpdateEventAction (design.md Decision 1). Editing only affects
 * occurrences generated after the save; already-generated occurrences
 * keep their snapshotted values.
 */
class EditEvent extends Component
{
    use ResolvesBrowserTimezone;
    use WithFileUploads;

    public Event $event;

    public Guild $guild;

    public string $title = '';

    public string $description = '';

    public mixed $image = null;

    public string $channelId = '';

    public ?int $eventRoleSetId = null;

    public string $postingMode = Event::POSTING_MODE_MESSAGE;

    public string $startDate = '';

    public string $startTime = '';

    public string $recurrenceType = 'none';

    public int $recurrenceInterval = 1;

    /** @var list<string> */
    public array $recurrenceDaysOfWeek = [];

    public string $recurrenceEndType = 'never';

    public string $recurrenceEndDate = '';

    public ?int $recurrenceEndCount = null;

    public function mount(Event $event, ParseRecurrenceRule $parseRecurrenceRule): void
    {
        $this->authorize('manage', $event);

        $this->event = $event;
        $this->guild = $event->guild;
        $this->title = $event->title;
        $this->description = $event->description;
        $this->channelId = $event->channel_id;
        $this->eventRoleSetId = $event->event_role_set_id;
        $this->postingMode = $event->posting_mode;

        if ($event->recurrence_start_at) {
            $local = $event->recurrence_start_at->clone()->setTimezone($event->recurrence_timezone ?? 'UTC');
            $this->startDate = $local->toDateString();
            $this->startTime = $local->format('H:i');
        }

        $parsed = $parseRecurrenceRule($event->recurrence_rule);
        $this->recurrenceType = $parsed['type'];
        $this->recurrenceInterval = $parsed['interval'];
        $this->recurrenceDaysOfWeek = $parsed['daysOfWeek'];
        $this->recurrenceEndType = $parsed['endType'];
        $this->recurrenceEndDate = $parsed['endDate']?->toDateString() ?? '';
        $this->recurrenceEndCount = $parsed['endCount'];
    }

    public function save(UpdateEventAction $updateEvent, BuildRecurrenceRule $buildRecurrenceRule): void
    {
        $this->authorize('manage', $this->event);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'channelId' => ['required', 'string'],
            'eventRoleSetId' => [
                'required', 'integer',
                'exists:event_role_sets,id,guild_id,'.$this->guild->id,
            ],
            'postingMode' => ['required', 'in:'.Event::POSTING_MODE_THREAD.','.Event::POSTING_MODE_MESSAGE],
            'image' => ['nullable', 'image', 'max:5120'],
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

        $roleSet = EventRoleSet::query()->findOrFail($validated['eventRoleSetId']);

        $imagePath = $this->image?->store('event-images', 'public') ?? $this->event->image_path;

        $this->event = $updateEvent->execute($this->event, [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'image_path' => $imagePath,
            'channel_id' => $validated['channelId'],
            'posting_mode' => $validated['postingMode'],
            'event_role_set_id' => $roleSet->id,
            'recurrence_rule' => $recurrenceRule,
            'recurrence_start_at' => $startAt,
            'recurrence_timezone' => $this->resolvedTimezone(),
        ]);

        $this->dispatch('event-updated', eventId: $this->event->id);
    }

    public function render(): View
    {
        return view('livewire.events.edit-event', [
            'roleSets' => $this->guild->eventRoleSets()->orderBy('name')->get(),
        ]);
    }
}
