<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendance extends Model
{
    /** @use HasFactory<\Database\Factories\EventAttendanceFactory> */
    use HasFactory;

    public const STATUS_ATTENDING = 'attending';

    public const STATUS_NOT_ATTENDING = 'not_attending';

    protected $fillable = [
        'event_occurrence_id',
        'discord_member_id',
        'status',
    ];

    /**
     * @return BelongsTo<EventOccurrence, $this>
     */
    public function eventOccurrence(): BelongsTo
    {
        return $this->belongsTo(EventOccurrence::class);
    }

    /**
     * @return BelongsTo<DiscordMember, $this>
     */
    public function discordMember(): BelongsTo
    {
        return $this->belongsTo(DiscordMember::class);
    }

    public function isNotAttending(): bool
    {
        return $this->status === self::STATUS_NOT_ATTENDING;
    }
}
