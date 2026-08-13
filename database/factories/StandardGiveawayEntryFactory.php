<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DiscordMember;
use App\Models\StandardGiveawayEntry;
use App\Models\StandardGiveawayOccurrence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StandardGiveawayEntry>
 */
class StandardGiveawayEntryFactory extends Factory
{
    protected $model = StandardGiveawayEntry::class;

    public function definition(): array
    {
        return [
            'standard_giveaway_occurrence_id' => StandardGiveawayOccurrence::factory(),
            'discord_member_id' => DiscordMember::factory(),
        ];
    }
}
