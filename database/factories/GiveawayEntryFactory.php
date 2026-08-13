<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CollectionThemeItem;
use App\Models\DiscordMember;
use App\Models\Giveaway;
use App\Models\GiveawayEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GiveawayEntry>
 */
class GiveawayEntryFactory extends Factory
{
    protected $model = GiveawayEntry::class;

    public function definition(): array
    {
        return [
            'giveaway_id' => Giveaway::factory(),
            'discord_member_id' => DiscordMember::factory(),
            'collection_theme_item_id' => CollectionThemeItem::factory(),
            'fulfilled_at' => null,
            'fulfilled_by_user_id' => null,
        ];
    }

    public function fulfilled(): static
    {
        return $this->state(fn (array $attributes) => [
            'fulfilled_at' => now(),
            'fulfilled_by_user_id' => \App\Models\User::factory(),
        ]);
    }
}
