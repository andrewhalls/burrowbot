<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandardGiveawayPrizeItem extends Model
{
    /** @use HasFactory<\Database\Factories\StandardGiveawayPrizeItemFactory> */
    use HasFactory;

    protected $fillable = [
        'standard_giveaway_id',
        'collection_theme_item_id',
    ];

    /**
     * @return BelongsTo<StandardGiveaway, $this>
     */
    public function standardGiveaway(): BelongsTo
    {
        return $this->belongsTo(StandardGiveaway::class);
    }

    /**
     * @return BelongsTo<CollectionThemeItem, $this>
     */
    public function collectionThemeItem(): BelongsTo
    {
        return $this->belongsTo(CollectionThemeItem::class);
    }
}
