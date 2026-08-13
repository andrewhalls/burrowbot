<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Giveaway extends Model
{
    /** @use HasFactory<\Database\Factories\GiveawayFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'guild_id',
        'collection_theme_id',
        'channel_id',
        'duration_minutes',
        'scheduled_start_at',
        'status',
        'discord_message_id',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'scheduled_start_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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
     * @return BelongsTo<CollectionTheme, $this>
     */
    public function collectionTheme(): BelongsTo
    {
        return $this->belongsTo(CollectionTheme::class);
    }

    /**
     * @return HasMany<GiveawayEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(GiveawayEntry::class);
    }

    /**
     * @return HasMany<DiscordOutboundAction, $this>
     */
    public function outboundActions(): HasMany
    {
        return $this->hasMany(DiscordOutboundAction::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * The authoritative expiry check: true once `now()` has passed `ends_at`,
     * independent of whether the `status` column has been flipped to
     * `closed` by the scheduled close job yet (design.md - "Authoritative
     * expiry enforcement").
     */
    public function hasExpired(?Carbon $now = null): bool
    {
        if ($this->ends_at === null) {
            return false;
        }

        return ($now ?? now())->greaterThanOrEqualTo($this->ends_at);
    }
}
