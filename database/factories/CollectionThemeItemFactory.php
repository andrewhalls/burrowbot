<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CollectionTheme;
use App\Models\CollectionThemeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectionThemeItem>
 */
class CollectionThemeItemFactory extends Factory
{
    protected $model = CollectionThemeItem::class;

    public function definition(): array
    {
        return [
            'collection_theme_id' => CollectionTheme::factory(),
            'name' => ucfirst(fake()->unique()->words(2, true)),
            'sort_order' => 0,
        ];
    }
}
