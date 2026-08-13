<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiveawayEntry extends Model
{
    /** @use HasFactory<\Database\Factories\GiveawayEntryFactory> */
    use HasFactory;

    /**
     * Entries are only ever created once and later patched with a
     * fulfilment timestamp - there is no general-purpose "updated_at".
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'giveaway_id',
        'discord_member_id',
        'collection_theme_item_id',
        'fulfilled_at',
        'fulfilled_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'fulfilled_at' => 'datetime',
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
     * @return BelongsTo<DiscordMember, $this>
     */
    public function discordMember(): BelongsTo
    {
        return $this->belongsTo(DiscordMember::class);
    }

    /**
     * @return BelongsTo<CollectionThemeItem, $this>
     */
    public function collectionThemeItem(): BelongsTo
    {
        return $this->belongsTo(CollectionThemeItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by_user_id');
    }

    public function isFulfilled(): bool
    {
        return $this->fulfilled_at !== null;
    }
}
