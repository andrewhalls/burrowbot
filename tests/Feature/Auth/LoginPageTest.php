<?php

declare(strict_types=1);

it('renders the Discord sign-in button with readable text on its accent background', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('Sign in with Discord')
        ->assertSeeHtml('text-accent-ink');
});
