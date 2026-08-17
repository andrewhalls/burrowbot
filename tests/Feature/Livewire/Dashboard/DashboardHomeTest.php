<?php

declare(strict_types=1);

use App\Livewire\Dashboard\DashboardHome;
use App\Models\Guild;
use App\Models\User;
use Livewire\Livewire;

it('lists guilds the user administers, linking into each guild pages', function () {
    $guild = Guild::factory()->create(['name' => 'Loot Shed']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(DashboardHome::class)
        ->assertSee('Loot Shed')
        ->assertSee(route('guilds.settings', $guild))
        ->assertSee(route('guilds.themes.index', $guild))
        ->assertSee(route('guilds.event-role-sets.index', $guild))
        ->assertSee(route('guilds.events.index', $guild))
        ->assertSee(route('guilds.giveaways.index', $guild))
        ->assertSee(route('guilds.standard-giveaways.index', $guild));
});

it('offers an "Add to another server" and "Refresh server list" link even when the user already administers guilds', function () {
    $guild = Guild::factory()->create(['name' => 'Loot Shed']);
    $staff = actingEventStaffFor($guild);

    Livewire::actingAs($staff)
        ->test(DashboardHome::class)
        ->assertSee('Add to another server')
        ->assertSee('Refresh server list')
        ->assertSee('discord.com/oauth2/authorize')
        ->assertSee(route('auth.discord.redirect'));
});

it('never lists a guild the user does not administer', function () {
    $administered = Guild::factory()->create(['name' => 'Mine']);
    $other = Guild::factory()->create(['name' => 'Not Mine']);
    $staff = actingEventStaffFor($administered);

    Livewire::actingAs($staff)
        ->test(DashboardHome::class)
        ->assertSee('Mine')
        ->assertDontSee('Not Mine');
});

it('shows onboarding instead of an empty list when the user administers no guilds', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(DashboardHome::class)
        ->assertSee('Invite bot to your server')
        ->assertSee('Manage Server')
        ->assertSee(route('auth.discord.redirect'));
});

it('renders as a full page over real HTTP, not just via the Livewire test harness', function () {
    // Livewire::test() above never exercises route-level page-layout
    // wrapping (config('livewire.component_layout')) the way a real HTTP
    // request does - this caught every full-page Livewire route in the app
    // 500ing with "No hint path defined for [layouts]" before config/livewire.php
    // was added to point that default at the actual `components.layout` shell.
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('<html', false);
    $response->assertSee('Signed in as');
});
