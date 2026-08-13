<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionThemeItem extends Model
{
    /** @use HasFactory<\Database\Factories\CollectionThemeItemFactory> */
    use HasFactory;

    protected $fillable = [
        'collection_theme_id',
        'name',
        'sort_order',
    ];

    /**
     * @return BelongsTo<CollectionTheme, $this>
     */
    public function collectionTheme(): BelongsTo
    {
        return $this->belongsTo(CollectionTheme::class);
    }
}
