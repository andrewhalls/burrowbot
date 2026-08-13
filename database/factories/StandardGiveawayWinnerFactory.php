<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CollectionThemeItem;
use App\Models\StandardGiveawayEntry;
use App\Models\StandardGiveawayOccurrence;
use App\Models\StandardGiveawayWinner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StandardGiveawayWinner>
 */
class StandardGiveawayWinnerFactory extends Factory
{
    protected $model = StandardGiveawayWinner::class;

    public function definition(): array
    {
        return [
            'standard_giveaway_occurrence_id' => StandardGiveawayOccurrence::factory(),
            'standard_giveaway_entry_id' => StandardGiveawayEntry::factory(),
            'collection_theme_item_id' => CollectionThemeItem::factory(),
            'drawn_at' => now(),
        ];
    }
}
