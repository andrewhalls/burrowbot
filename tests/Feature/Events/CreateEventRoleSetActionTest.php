<?php

declare(strict_types=1);

use App\Actions\EventRoleSets\CreateEventRoleSetAction;
use App\Models\EventRole;
use App\Models\EventRoleSet;
use App\Models\Guild;

it('creates a role set with its roles in order', function () {
    $guild = Guild::factory()->create();

    $roleSet = (new CreateEventRoleSetAction)->execute($guild, 'Raid Roles', false, [
        ['name' => 'Tank', 'capacity_mode' => 'capped', 'capacity' => 2],
        ['name' => 'Healer', 'capacity_mode' => 'waitlisted', 'capacity' => 2],
        ['name' => 'DPS', 'capacity_mode' => 'uncapped', 'capacity' => null],
    ]);

    expect($roleSet)->toBeInstanceOf(EventRoleSet::class)
        ->and($roleSet->guild_id)->toBe($guild->id)
        ->and($roleSet->allow_multiple_roles)->toBeFalse()
        ->and($roleSet->roles->pluck('name')->all())->toBe(['Tank', 'Healer', 'DPS']);

    expect($roleSet->roles[0]->capacity_mode)->toBe('capped')
        ->and($roleSet->roles[0]->capacity)->toBe(2)
        ->and($roleSet->roles[2]->capacity)->toBeNull();
});

it('stores the discord_role_id on each created role', function () {
    $guild = Guild::factory()->create();

    $roleSet = (new CreateEventRoleSetAction)->execute($guild, 'Raid Roles', false, [
        ['name' => 'Tank', 'discord_role_id' => '111', 'capacity_mode' => 'uncapped', 'capacity' => null],
    ]);

    expect($roleSet->roles->first()->discord_role_id)->toBe('111');
});

it('discards blank role rows before validating', function () {
    $guild = Guild::factory()->create();

    $roleSet = (new CreateEventRoleSetAction)->execute($guild, 'Roles', false, [
        ['name' => 'Tank', 'capacity_mode' => 'uncapped', 'capacity' => null],
        ['name' => '', 'capacity_mode' => 'uncapped', 'capacity' => null],
    ]);

    expect($roleSet->roles)->toHaveCount(1);
});

it('rejects a role set with zero non-blank roles and creates nothing', function () {
    $guild = Guild::factory()->create();

    expect(fn () => (new CreateEventRoleSetAction)->execute($guild, 'Empty', false, [
        ['name' => '', 'capacity_mode' => 'uncapped', 'capacity' => null],
    ]))->toThrow(InvalidArgumentException::class);

    expect(EventRoleSet::query()->count())->toBe(0);
});

it('rejects a capped role with no capacity and creates nothing', function () {
    $guild = Guild::factory()->create();

    expect(fn () => (new CreateEventRoleSetAction)->execute($guild, 'Roles', false, [
        ['name' => 'Tank', 'capacity_mode' => 'capped', 'capacity' => null],
    ]))->toThrow(InvalidArgumentException::class);

    expect(EventRoleSet::query()->count())->toBe(0)
        ->and(EventRole::query()->count())->toBe(0);
});
