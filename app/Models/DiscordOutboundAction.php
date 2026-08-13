<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscordOutboundAction extends Model
{
    /** @use HasFactory<\Database\Factories\DiscordOutboundActionFactory> */
    use HasFactory;

    public const TYPE_POST_GIVEAWAY_MESSAGE = 'post_giveaway_message';

    public const TYPE_CLOSE_GIVEAWAY_MESSAGE = 'close_giveaway_message';

    public const TYPE_POST_EVENT_OCCURRENCE_THREAD = 'post_event_occurrence_thread';

    public const TYPE_POST_EVENT_OCCURRENCE_MESSAGE = 'post_event_occurrence_message';

    public const TYPE_POST_STANDARD_GIVEAWAY_THREAD = 'post_standard_giveaway_thread';

    public const TYPE_POST_STANDARD_GIVEAWAY_MESSAGE = 'post_standard_giveaway_message';

    public const TYPE_ANNOUNCE_STANDARD_GIVEAWAY_WINNERS = 'announce_standard_giveaway_winners';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACKED = 'acked';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'type',
        'giveaway_id',
        'event_occurrence_id',
        'standard_giveaway_occurrence_id',
        'payload',
        'status',
        'attempts',
        'last_failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Giveaway, $this>
     */
    public function giveaway(): BelongsTo
    {
        return $this->belongsTo(Giveaway::class);
    }

    /**
     * @return BelongsTo<EventOccurrence, $this>
     */
    public function eventOccurrence(): BelongsTo
    {
        return $this->belongsTo(EventOccurrence::class);
    }

    /**
     * @return BelongsTo<StandardGiveawayOccurrence, $this>
     */
    public function standardGiveawayOccurrence(): BelongsTo
    {
        return $this->belongsTo(StandardGiveawayOccurrence::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
