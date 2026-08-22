<?php

declare(strict_types=1);

namespace App\Livewire\Broadcasts;

use App\Actions\Broadcasts\CreateBroadcastAction;
use App\Livewire\Concerns\ResolvesBrowserTimezone;
use App\Models\Guild;
use App\Support\Events\BuildRecurrenceRule;
use App\Support\GuildAdmins\GuildAdminSection;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Component;

class CreateBroadcast extends Component
{
    use ResolvesBrowserTimezone;

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

    public function mount(Guild $guild): void
    {
        abort_unless(Auth::user()->hasGuildAdminSection($guild, GuildAdminSection::BROADCASTS), 403);

        $this->guild = $guild;
        $this->channelId = (string) ($guild->default_channel_id ?? '');
    }

    public function save(CreateBroadcastAction $createBroadcast, BuildRecurrenceRule $buildRecurrenceRule): void
    {
        abort_unless(Auth::user()->hasGuildAdminSection($this->guild, GuildAdminSection::BROADCASTS), 403);

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

        // Deliberately NOT ->utc() here - see CreateEvent::save() for why:
        // $startAt/$recurrenceEndDate must keep the admin's local
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

        $broadcast = $createBroadcast->execute(
            $this->guild,
            $validated['title'],
            $validated['messageTemplate'],
            $validated['channelId'],
            $recurrenceRule,
            $startAt,
            $this->resolvedTimezone(),
            Auth::user(),
        );

        $this->dispatch('broadcast-created', broadcastId: $broadcast->id);
    }

    public function render(): View
    {
        return view('livewire.broadcasts.create-broadcast');
    }
}
