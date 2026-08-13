<?php

declare(strict_types=1);

use App\Models\Guild;

// Nav now lives in the dashboard shell (layout.blade.php's sidebar/top bar,
// see improve-dashboard-shell), which is only rendered on a real HTTP
// request - Livewire::test() bypasses route-level layout wrapping entirely
// (same reason the DashboardHome "real HTTP" test exists), so these use
// $this->get() instead.

it('shows links to the same guild other pages when viewing a guild-scoped page', function () {
    $guild = Guild::factory()->create();
    $staff = actingEventStaffFor($guild);

    $response = $this->actingAs($staff)->get(route('guilds.events.index', $guild));

    $response->assertOk()
        ->assertSee(route('guilds.settings', $guild), false)
        ->assertSee(route('guilds.themes.index', $guild), false)
        ->assertSee(route('guilds.event-role-sets.index', $guild), false)
        ->assertSee(route('guilds.giveaways.create', $guild), false)
        ->assertSee(route('guilds.standard-giveaways.index', $guild), false)
        ->assertSee(route('dashboard'), false);
});

it('never links to another guild pages', function () {
    $guild = Guild::factory()->create();
    $otherGuild = Guild::factory()->create();
    $staff = actingEventStaffFor($guild);

    $response = $this->actingAs($staff)->get(route('guilds.events.index', $guild));

    $response->assertOk()
        ->assertDontSee(route('guilds.settings', $otherGuild), false)
        ->assertDontSee(route('guilds.themes.index', $otherGuild), false)
        ->assertDontSee(route('guilds.events.index', $otherGuild), false);
});
