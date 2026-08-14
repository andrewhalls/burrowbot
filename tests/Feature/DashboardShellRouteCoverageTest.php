<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\EventOccurrence;
use App\Models\EventRole;
use App\Models\EventRoleSet;
use App\Models\Giveaway;
use App\Models\GuildAdmin;
use App\Models\StandardGiveawayOccurrence;
use App\Models\User;

/**
 * The dashboard shell (sidebar/top bar, guild resolution) wraps every
 * full-page route via layout.blade.php - this is a broad regression guard
 * that every one of them still renders after a shell/layout change,
 * including the routes whose page component doesn't itself type-hint
 * `Guild $guild` (event-occurrences, giveaways.show, standard-giveaway-
 * occurrences), which turned out to need the guild-resolution fallback in
 * layout.blade.php added alongside this test.
 */
it('renders every full-page guild-scoped route, and the dashboard, over real HTTP', function () {
    $roleSet = EventRoleSet::factory()->create();
    EventRole::factory()->for($roleSet, 'eventRoleSet')->create();
    $guild = $roleSet->guild;
    $user = User::factory()->create();
    GuildAdmin::factory()->for($guild)->for($user)->create();

    $event = Event::factory()->for($roleSet, 'eventRoleSet')->for($guild)->create();
    $occurrence = EventOccurrence::factory()->create(['event_id' => $event->id, 'event_role_set_id' => $roleSet->id]);
    $giveaway = Giveaway::factory()->for($guild)->create();
    $stdOccurrence = StandardGiveawayOccurrence::factory()->posted()->create();
    $stdGuild = $stdOccurrence->standardGiveaway->guild;
    GuildAdmin::factory()->for($stdGuild)->for($user)->create();

    $routes = [
        route('dashboard'),
        route('guilds.settings', $guild),
        route('guilds.themes.index', $guild),
        route('guilds.event-role-sets.index', $guild),
        route('guilds.events.index', $guild),
        route('guilds.event-occurrences.show', [$guild, $occurrence]),
        route('guilds.giveaways.index', $guild),
        route('guilds.giveaways.create', $guild),
        route('guilds.giveaways.show', [$guild, $giveaway]),
        route('guilds.standard-giveaways.index', $stdGuild),
        route('guilds.standard-giveaway-occurrences.show', [$stdGuild, $stdOccurrence]),
    ];

    foreach ($routes as $url) {
        $response = $this->actingAs($user)->get($url);
        expect($response->status())->toBe(200, "Expected 200 for {$url}, got {$response->status()}");
    }
});
