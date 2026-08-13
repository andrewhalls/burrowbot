<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionTheme extends Model
{
    /** @use HasFactory<\Database\Factories\CollectionThemeFactory> */
    use HasFactory;

    protected $fillable = [
        'guild_id',
        'name',
    ];

    /**
     * @return BelongsTo<Guild, $this>
     */
    public function guild(): BelongsTo
    {
        return $this->belongsTo(Guild::class);
    }

    /**
     * @return HasMany<CollectionThemeItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CollectionThemeItem::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Giveaway, $this>
     */
    public function giveaways(): HasMany
    {
        return $this->hasMany(Giveaway::class);
    }

    /**
     * Whether this collection theme's item list may currently be edited: it
     * must not be backing any giveaway that is still active.
     */
    public function isEditable(): bool
    {
        return ! $this->giveaways()->where('status', Giveaway::STATUS_ACTIVE)->exists();
    }
}
