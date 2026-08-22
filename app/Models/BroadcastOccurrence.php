<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BroadcastOccurrenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BroadcastOccurrence extends Model
{
    /** @use HasFactory<BroadcastOccurrenceFactory> */
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_POSTED = 'posted';

    protected $fillable = [
        'broadcast_id',
        'message_template',
        'channel_id',
        'scheduled_post_at',
        'status',
        'posted_at',
        'discord_message_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_post_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Broadcast, $this>
     */
    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(Broadcast::class);
    }

    /**
     * @return HasMany<DiscordOutboundAction, $this>
     */
    public function outboundActions(): HasMany
    {
        return $this->hasMany(DiscordOutboundAction::class);
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }
}
