<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRoleSignup extends Model
{
    /** @use HasFactory<\Database\Factories\EventRoleSignupFactory> */
    use HasFactory;

    /**
     * Signup rows are only ever created or deleted, never generally
     * updated - except flipping is_waitlisted on promotion - and there is
     * no general-purpose "updated_at".
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'event_occurrence_id',
        'discord_member_id',
        'event_role_id',
        'is_waitlisted',
    ];

    protected function casts(): array
    {
        return [
            'is_waitlisted' => 'boolean',
        ];
    }

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

    /**
     * @return BelongsTo<EventRole, $this>
     */
    public function eventRole(): BelongsTo
    {
        return $this->belongsTo(EventRole::class);
    }
}
