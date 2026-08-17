<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
        'created_by_user_id',
        'title',
        'description',
        'image_path',
        'banner_image_path',
        'channel_id',
        'posting_mode',
        'status',
        'archived_at',
        'winner_count',
        'requires_booster',
        'duration_minutes',
        'claim_link',
        'claim_deadline_hours',
        'congrats_message_template',
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
            'claim_deadline_hours' => 'integer',
            'recurrence_start_at' => 'datetime',
            'archived_at' => 'datetime',
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
     * @return Attribute<string|null, never>
     */
    protected function bannerImageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->banner_image_path ? Storage::disk('public')->url($this->banner_image_path) : null,
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

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Whether this series is still safe to delete: true as long as none of
     * its occurrences have reached Discord (posted or closed) - used by
     * DeleteStandardGiveawayAction and to decide whether to offer the
     * Delete action in the dashboard.
     */
    public function isDeletable(): bool
    {
        return $this->occurrences()
            ->whereIn('status', [StandardGiveawayOccurrence::STATUS_POSTED, StandardGiveawayOccurrence::STATUS_CLOSED])
            ->doesntExist();
    }
}
