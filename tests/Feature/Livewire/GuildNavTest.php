<?php

declare(strict_types=1);

use App\Livewire\Events\EventIndex;
use App\Models\Guild;
use Livewire\Livewire;

it('shows links to the same guild other pages when viewing a guild-scoped page', function () {
    $guild = Guild::factory()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->assertSee(route('guilds.settings', $guild))
        ->assertSee(route('guilds.themes.index', $guild))
        ->assertSee(route('guilds.event-role-sets.index', $guild))
        ->assertSee(route('guilds.giveaways.create', $guild))
        ->assertSee(route('guilds.standard-giveaways.index', $guild))
        ->assertSee(route('dashboard'));
});

it('never links to another guild pages', function () {
    $guild = Guild::factory()->create();
    $otherGuild = Guild::factory()->create();
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(EventIndex::class, ['guild' => $guild])
        ->assertDontSee(route('guilds.settings', $otherGuild))
        ->assertDontSee(route('guilds.themes.index', $otherGuild))
        ->assertDontSee(route('guilds.events.index', $otherGuild));
});
