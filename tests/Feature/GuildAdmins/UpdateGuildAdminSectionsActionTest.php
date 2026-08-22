<?php

declare(strict_types=1);

use App\Actions\GuildAdmins\UpdateGuildAdminSectionsAction;
use App\Models\GuildAdmin;

it('replaces a granted admin\'s sections', function () {
    $admin = GuildAdmin::factory()->granted(['giveaways'])->create();

    (new UpdateGuildAdminSectionsAction)->execute($admin, ['broadcasts', 'events']);

    expect($admin->fresh()->sections)->toBe(['broadcasts', 'events']);
});

it('adding a section keeps the previously granted ones intact when included', function () {
    $admin = GuildAdmin::factory()->granted(['giveaways'])->create();

    (new UpdateGuildAdminSectionsAction)->execute($admin, ['giveaways', 'broadcasts']);

    expect($admin->fresh()->sections)->toBe(['giveaways', 'broadcasts']);
});

it('removing a section from the list revokes just that one', function () {
    $admin = GuildAdmin::factory()->granted(['giveaways', 'broadcasts'])->create();

    (new UpdateGuildAdminSectionsAction)->execute($admin, ['giveaways']);

    expect($admin->fresh()->sections)->toBe(['giveaways']);
});
