<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class EventOccurrence extends Model
{
    /** @use HasFactory<\Database\Factories\EventOccurrenceFactory> */
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_POSTED = 'posted';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'event_id',
        'title',
        'description',
        'channel_id',
        'posting_mode',
        'event_role_set_id',
        'scheduled_start_at',
        'status',
        'discord_thread_id',
        'discord_message_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_start_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<EventRoleSet, $this>
     */
    public function eventRoleSet(): BelongsTo
    {
        return $this->belongsTo(EventRoleSet::class);
    }

    /**
     * @return HasMany<EventAttendance, $this>
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    /**
     * @return HasMany<EventRoleSignup, $this>
     */
    public function roleSignups(): HasMany
    {
        return $this->hasMany(EventRoleSignup::class);
    }

    /**
     * @return HasMany<DiscordOutboundAction, $this>
     */
    public function outboundActions(): HasMany
    {
        return $this->hasMany(DiscordOutboundAction::class);
    }

    public function isThreadMode(): bool
    {
        return $this->posting_mode === Event::POSTING_MODE_THREAD;
    }

    /**
     * The authoritative signup cutoff: true once `now()` has passed
     * `scheduled_start_at`, independent of `status` - mirrors
     * Giveaway::hasExpired().
     */
    public function hasStarted(?Carbon $now = null): bool
    {
        return ($now ?? now())->greaterThanOrEqualTo($this->scheduled_start_at);
    }
}
