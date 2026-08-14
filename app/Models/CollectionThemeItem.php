<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CollectionThemeItem extends Model
{
    /** @use HasFactory<\Database\Factories\CollectionThemeItemFactory> */
    use HasFactory;

    protected $fillable = [
        'collection_theme_id',
        'name',
        'image_path',
        'sort_order',
    ];

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
     * @return BelongsTo<CollectionTheme, $this>
     */
    public function collectionTheme(): BelongsTo
    {
        return $this->belongsTo(CollectionTheme::class);
    }
}
