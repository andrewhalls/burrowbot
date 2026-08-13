<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StandardGiveawayEntry extends Model
{
    /** @use HasFactory<\Database\Factories\StandardGiveawayEntryFactory> */
    use HasFactory;

    /**
     * Entries are only ever created, never updated - there is no
     * general-purpose "updated_at".
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'standard_giveaway_occurrence_id',
        'discord_member_id',
    ];

    /**
     * @return BelongsTo<StandardGiveawayOccurrence, $this>
     */
    public function standardGiveawayOccurrence(): BelongsTo
    {
        return $this->belongsTo(StandardGiveawayOccurrence::class);
    }

    /**
     * @return BelongsTo<DiscordMember, $this>
     */
    public function discordMember(): BelongsTo
    {
        return $this->belongsTo(DiscordMember::class);
    }

    /**
     * @return HasOne<StandardGiveawayWinner, $this>
     */
    public function win(): HasOne
    {
        return $this->hasOne(StandardGiveawayWinner::class);
    }

    public function isWinner(): bool
    {
        return $this->win()->exists();
    }
}
