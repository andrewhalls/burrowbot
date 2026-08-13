<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CollectionThemeItem;
use App\Models\StandardGiveaway;
use App\Models\StandardGiveawayPrizeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StandardGiveawayPrizeItem>
 */
class StandardGiveawayPrizeItemFactory extends Factory
{
    protected $model = StandardGiveawayPrizeItem::class;

    public function definition(): array
    {
        return [
            'standard_giveaway_id' => StandardGiveaway::factory(),
            'collection_theme_item_id' => CollectionThemeItem::factory(),
        ];
    }
}
