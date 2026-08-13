<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StandardGiveawayWinner extends Model
{
    /** @use HasFactory<\Database\Factories\StandardGiveawayWinnerFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'standard_giveaway_occurrence_id',
        'standard_giveaway_entry_id',
        'collection_theme_item_id',
        'drawn_at',
    ];

    protected function casts(): array
    {
        return [
            'drawn_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<StandardGiveawayOccurrence, $this>
     */
    public function standardGiveawayOccurrence(): BelongsTo
    {
        return $this->belongsTo(StandardGiveawayOccurrence::class);
    }

    /**
     * @return BelongsTo<StandardGiveawayEntry, $this>
     */
    public function standardGiveawayEntry(): BelongsTo
    {
        return $this->belongsTo(StandardGiveawayEntry::class);
    }

    /**
     * @return BelongsTo<CollectionThemeItem, $this>
     */
    public function collectionThemeItem(): BelongsTo
    {
        return $this->belongsTo(CollectionThemeItem::class);
    }
}
