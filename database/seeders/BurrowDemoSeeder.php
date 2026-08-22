<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Broadcasts\CreateBroadcastAction;
use App\Actions\Events\CreateEventAction;
use App\Actions\StandardGiveaways\CreateStandardGiveawayAction;
use App\Models\CollectionTheme;
use App\Models\DiscordMember;
use App\Models\EventRole;
use App\Models\EventRoleSet;
use App\Models\Giveaway;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local/demo data: a guild, a full admin for it, a scoped admin limited to
 * popup giveaways (for manual QA of add-guild-admin-permissions), a
 * collection theme with items, a sample draft giveaway, a "Raid Roles"
 * event role set, a recurring weekly event, a recurring booster-only
 * standard giveaway, and a recurring weekly broadcast - enough to click
 * around the dashboard without configuring a real Discord application first.
 */
class BurrowDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Demo Admin',
            'email' => 'admin@example.com',
            'discord_user_id' => '000000000000000001',
        ]);

        $guild = Guild::factory()->create([
            'discord_guild_id' => '000000000000000100',
            'name' => 'Demo Server',
        ]);

        GuildAdmin::factory()->for($guild)->for($admin)->discordSynced()->create();

        $giveawayModerator = User::factory()->create([
            'name' => 'Giveaway Mod',
            'email' => 'giveaway-mod@example.com',
            'discord_user_id' => '000000000000000002',
        ]);

        DiscordMember::factory()->for($guild)->create([
            'discord_user_id' => '000000000000000002',
            'username' => 'giveaway-mod',
        ]);

        GuildAdmin::factory()->for($guild)->for($giveawayModerator)->granted(['giveaways'])->create([
            'discord_user_id' => '000000000000000002',
        ]);

        $theme = CollectionTheme::factory()
            ->for($guild)
            ->withItems(0)
            ->create(['name' => 'Retro Arcade']);

        $themeItems = collect(['Joystick', 'Cartridge', 'Coin Pouch', 'High Score Trophy'])
            ->map(fn (string $name, int $index) => $theme->items()->create([
                'name' => $name,
                'sort_order' => $index,
            ]));

        Giveaway::factory()
            ->for($guild)
            ->for($theme, 'collectionTheme')
            ->create([
                'channel_id' => '000000000000000200',
                'duration_minutes' => 30,
            ]);

        $roleSet = EventRoleSet::factory()
            ->for($guild)
            ->create(['name' => 'Raid Roles', 'allow_multiple_roles' => false]);

        EventRole::query()->insert([
            ['event_role_set_id' => $roleSet->id, 'name' => 'Tank', 'sort_order' => 0, 'capacity_mode' => EventRole::CAPACITY_CAPPED, 'capacity' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['event_role_set_id' => $roleSet->id, 'name' => 'Healer', 'sort_order' => 1, 'capacity_mode' => EventRole::CAPACITY_WAITLISTED, 'capacity' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['event_role_set_id' => $roleSet->id, 'name' => 'DPS', 'sort_order' => 2, 'capacity_mode' => EventRole::CAPACITY_UNCAPPED, 'capacity' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        app(CreateEventAction::class)->execute(
            $guild,
            $roleSet,
            'Weekly Raid Night',
            'Come prepared with consumables. Roles below.',
            '000000000000000300',
            'thread',
            'FREQ=WEEKLY;BYDAY=WE',
            now()->next('Wednesday')->setTime(20, 0),
            'UTC',
        );

        app(CreateStandardGiveawayAction::class)->execute(
            $guild,
            'Nitro Friday',
            'Server boosters get one free entry - a winner is drawn every Friday.',
            '000000000000000400',
            'message',
            1,
            true,
            60,
            [$themeItems->first()->id],
            [],
            'FREQ=WEEKLY;BYDAY=FR',
            now()->next('Friday')->setTime(18, 0),
            'UTC',
        );

        app(CreateBroadcastAction::class)->execute(
            $guild,
            'Raid Reset Reminder',
            'Heads up {{guild_name}}! Raid reset is today at {{time}} in {{channel}}. Next reminder: {{next_occurrence_date}}.',
            '000000000000000300',
            'FREQ=WEEKLY;BYDAY=WE',
            now()->next('Wednesday')->setTime(9, 0),
            'UTC',
        );
    }
}
