<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BroadcastFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Broadcast extends Model
{
    /** @use HasFactory<BroadcastFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'guild_id',
        'created_by_user_id',
        'title',
        'message_template',
        'channel_id',
        'status',
        'archived_at',
        'recurrence_rule',
        'recurrence_start_at',
        'recurrence_timezone',
    ];

    protected function casts(): array
    {
        return [
            'recurrence_start_at' => 'datetime',
            'archived_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @return HasMany<BroadcastOccurrence, $this>
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(BroadcastOccurrence::class);
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
     * its occurrences have been posted to Discord - used by
     * DeleteBroadcastAction and to decide whether to offer the Delete
     * action in the dashboard.
     */
    public function isDeletable(): bool
    {
        return $this->occurrences()
            ->where('status', BroadcastOccurrence::STATUS_POSTED)
            ->doesntExist();
    }
}
