<?php

declare(strict_types=1);

use App\Support\Discord\BotInviteUrl;

it('builds an invite url with the configured client id', function () {
    config(['services.discord.client_id' => '123456789']);

    expect(BotInviteUrl::build())->toContain('client_id=123456789');
});

it('requests only the bot scope, never applications.commands', function () {
    $url = BotInviteUrl::build();

    expect($url)->toContain('scope=bot')
        ->and($url)->not->toContain('applications.commands');
});

it('requests the exact documented permissions integer', function () {
    $url = BotInviteUrl::build();

    expect(BotInviteUrl::PERMISSIONS)->toBe(292057992192)
        ->and($url)->toContain('permissions=292057992192');
});
