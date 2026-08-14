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

class Giveaway extends Model
{
    /** @use HasFactory<\Database\Factories\GiveawayFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'guild_id',
        'created_by_user_id',
        'collection_theme_id',
        'channel_id',
        'duration_minutes',
        'scheduled_start_at',
        'description',
        'image_path',
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
     * The image's fetchable URL, computed on demand from the stored relative
     * path (design.md Decision 1) - never stored in the DB so the app's
     * domain can change without a data migration.
     *
     * @return Attribute<string|null, never>
     */
    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
        );
    }

    /**
     * @return BelongsTo<Guild, $this>
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
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
