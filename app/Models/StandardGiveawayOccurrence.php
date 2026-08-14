<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class StandardGiveawayOccurrence extends Model
{
    /** @use HasFactory<\Database\Factories\StandardGiveawayOccurrenceFactory> */
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_POSTED = 'posted';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'standard_giveaway_id',
        'title',
        'description',
        'image_path',
        'channel_id',
        'posting_mode',
        'requires_booster',
        'winner_count',
        'duration_minutes',
        'prize_item_ids',
        'required_role_ids',
        'scheduled_post_at',
        'status',
        'posted_at',
        'ends_at',
        'discord_thread_id',
        'discord_message_id',
    ];

    protected function casts(): array
    {
        return [
            'requires_booster' => 'boolean',
            'winner_count' => 'integer',
            'duration_minutes' => 'integer',
            'prize_item_ids' => 'array',
            'required_role_ids' => 'array',
            'scheduled_post_at' => 'datetime',
            'posted_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
        );
    }

    /**
     * @return BelongsTo<StandardGiveaway, $this>
     */
    public function standardGiveaway(): BelongsTo
    {
        return $this->belongsTo(StandardGiveaway::class);
    }

    /**
     * @return HasMany<StandardGiveawayEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(StandardGiveawayEntry::class);
    }

    /**
     * @return HasMany<StandardGiveawayWinner, $this>
     */
    public function winners(): HasMany
    {
        return $this->hasMany(StandardGiveawayWinner::class);
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

    public function isThreadMode(): bool
    {
        return $this->posting_mode === StandardGiveaway::POSTING_MODE_THREAD;
    }

    /**
     * The authoritative entry cutoff: true once `now()` has passed
     * `ends_at`, independent of `status` - mirrors Giveaway::hasExpired()
     * and EventOccurrence::hasStarted().
     */
    public function hasEnded(?Carbon $now = null): bool
    {
        if ($this->ends_at === null) {
            return false;
        }

        return ($now ?? now())->greaterThanOrEqualTo($this->ends_at);
    }
}
