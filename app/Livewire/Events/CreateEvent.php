<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Actions\Events\CreateEventAction;
use App\Models\Event;
use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Support\Events\BuildRecurrenceRule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Livewire\Component;

class CreateEvent extends Component
{
    public Guild $guild;

    public string $title = '';

    public string $description = '';

    public string $channelId = '';

    public ?int $eventRoleSetId = null;

    public string $postingMode = Event::POSTING_MODE_MESSAGE;

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

    public function save(CreateEventAction $createEvent, BuildRecurrenceRule $buildRecurrenceRule): void
    {
        $this->authorize('manage', $this->guild);

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'channelId' => ['required', 'string'],
            'eventRoleSetId' => [
                'required', 'integer',
                'exists:event_role_sets,id,guild_id,'.$this->guild->id,
            ],
            'postingMode' => ['required', 'in:'.Event::POSTING_MODE_THREAD.','.Event::POSTING_MODE_MESSAGE],
            'startDate' => ['required', 'date'],
            'startTime' => ['required', 'date_format:H:i'],
            'timezone' => ['required', 'timezone'],
            'recurrenceType' => ['required', 'in:none,daily,weekly,monthly'],
            'recurrenceInterval' => ['required', 'integer', 'min:1'],
            'recurrenceEndType' => ['required', 'in:never,on_date,after_count'],
        ]);

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

        $roleSet = EventRoleSet::query()->findOrFail($validated['eventRoleSetId']);

        $event = $createEvent->execute(
            $this->guild,
            $roleSet,
            $validated['title'],
            $validated['description'],
            $validated['channelId'],
            $validated['postingMode'],
            $recurrenceRule,
            $startAt,
            $this->timezone,
        );

        $this->dispatch('event-created', eventId: $event->id);
    }

    public function render(): View
    {
        return view('livewire.events.create-event', [
            'roleSets' => $this->guild->eventRoleSets()->orderBy('name')->get(),
        ]);
    }
}
