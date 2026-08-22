<?php

declare(strict_types=1);

use App\Actions\GuildAdmins\RevokeGuildAdminAction;
use App\Models\GuildAdmin;

it('revokes a granted admin', function () {
    $admin = GuildAdmin::factory()->granted(['giveaways'])->create();

    (new RevokeGuildAdminAction)->execute($admin);

    expect(GuildAdmin::query()->find($admin->id))->toBeNull();
});

it('rejects revoking a discord-synced (full) admin', function () {
    $admin = GuildAdmin::factory()->discordSynced()->create();

    expect(fn () => (new RevokeGuildAdminAction)->execute($admin))
        ->toThrow(InvalidArgumentException::class);

    expect(GuildAdmin::query()->find($admin->id))->not->toBeNull();
});
