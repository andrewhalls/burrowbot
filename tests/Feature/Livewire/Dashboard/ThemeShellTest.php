<?php

declare(strict_types=1);

use App\Models\User;

it('includes the no-flash theme script and toggle control on a rendered page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk()
        ->assertSee("localStorage.getItem('theme')", false)
        ->assertSee('window.burrowToggleTheme', false)
        ->assertSee('burrowToggleTheme()', false);
});

it('renders dark as the default with no data-theme attribute in the server-rendered HTML', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk()->assertDontSee('data-theme="light"', false);
});
