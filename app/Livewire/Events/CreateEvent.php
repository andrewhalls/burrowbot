<?php

declare(strict_types=1);

namespace App\Livewire\Events;

use App\Actions\Events\CreateEventAction;
use App\Livewire\Concerns\ResolvesBrowserTimezone;
use App\Models\Event;
use App\Models\EventRoleSet;
use App\Models\Guild;
use App\Support\Events\BuildRecurrenceRule;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateEvent extends Component
{
    use ResolvesBrowserTimezone;
    use WithFileUploads;

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

    public function mount(Guild $guild): void
    {
        $this->authorize('manage', $guild);

        $this->guild = $guild;
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
            'image' => ['nullable', 'image', 'max:5120'],
            'startDate' => ['required', 'date'],
            'startTime' => ['required', 'date_format:H:i'],
            'recurrenceType' => ['required', 'in:none,daily,weekly,monthly'],
            'recurrenceInterval' => ['required', 'integer', 'min:1'],
            'recurrenceEndType' => ['required', 'in:never,on_date,after_count'],
        ]);

        // Deliberately NOT ->utc() here - see CreateStandardGiveaway::save()
        // for why: $startAt/$recurrenceEndDate must keep the admin's local
        // wall-clock numbers, paired with the separately-passed timezone
        // below, for ExpandRecurrenceRule to regenerate future occurrences
        // at the same local time.
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

        $imagePath = $this->image?->store('event-images', 'public');

        $event = $createEvent->execute(
            $this->guild,
            $roleSet,
            $validated['title'],
            $validated['description'],
            $validated['channelId'],
            $validated['postingMode'],
            $recurrenceRule,
            $startAt,
            $this->resolvedTimezone(),
            $imagePath,
            Auth::user(),
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
