<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StandardGiveaway extends Model
{
    /** @use HasFactory<\Database\Factories\StandardGiveawayFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_CANCELLED = 'cancelled';

    public const POSTING_MODE_THREAD = 'thread';

    public const POSTING_MODE_MESSAGE = 'message';

    protected $fillable = [
        'guild_id',
        'title',
        'description',
        'channel_id',
        'posting_mode',
        'status',
        'winner_count',
        'requires_booster',
        'duration_minutes',
        'recurrence_rule',
        'recurrence_start_at',
        'recurrence_timezone',
    ];

    protected function casts(): array
    {
        return [
            'winner_count' => 'integer',
            'requires_booster' => 'boolean',
            'duration_minutes' => 'integer',
            'recurrence_start_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Guild, $this>
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    /**
     * @return HasMany<StandardGiveawayPrizeItem, $this>
     */
    public function prizeItems(): HasMany
    {
        return $this->hasMany(StandardGiveawayPrizeItem::class);
    }

    /**
     * @return HasMany<StandardGiveawayRequiredRole, $this>
     */
    public function requiredRoles(): HasMany
    {
        return $this->hasMany(StandardGiveawayRequiredRole::class);
    }

    /**
     * @return HasMany<StandardGiveawayOccurrence, $this>
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(StandardGiveawayOccurrence::class);
    }

    public function isRecurring(): bool
    {
        return $this->recurrence_rule !== null;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
