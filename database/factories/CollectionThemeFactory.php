<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CollectionTheme;
use App\Models\CollectionThemeItem;
use App\Models\Guild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollectionTheme>
 */
class CollectionThemeFactory extends Factory
{
    protected $model = CollectionTheme::class;

    public function definition(): array
    {
        return [
            'guild_id' => Guild::factory(),
            'name' => fake()->unique()->words(2, true).' Theme',
        ];
    }

    /**
     * Attach a given number of items after creating the collection theme.
     */
    public function withItems(int $count = 3): static
    {
        return $this->afterCreating(function (CollectionTheme $theme) use ($count): void {
            CollectionThemeItem::factory()
                ->count($count)
                ->sequence(fn ($sequence) => ['sort_order' => $sequence->index])
                ->for($theme, 'collectionTheme')
                ->create();
        });
    }
}
