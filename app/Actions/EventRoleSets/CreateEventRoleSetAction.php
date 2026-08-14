<?php

declare(strict_types=1);

namespace App\Actions\EventRoleSets;

use App\Models\EventRole;
use App\Models\EventRoleSet;
use App\Models\Guild;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Creates a role set with its roles in one transaction.
 *
 * See openspec specs/event-role-sets - "Role set creation": a role set with
 * zero roles must be rejected and nothing created.
 *
 * @param  list<array{name: string, capacity_mode: string, capacity: int|null}>  $roles
 */
class CreateEventRoleSetAction
{
    public function execute(Guild $guild, string $name, bool $allowMultipleRoles, array $roles): EventRoleSet
    {
        $roles = array_values(array_filter(
            $roles,
            fn (array $role) => trim($role['name'] ?? '') !== '',
        ));

        if ($roles === []) {
            throw new InvalidArgumentException('A role set must have at least one role.');
        }

        foreach ($roles as $role) {
            $this->assertValidCapacity($role);
        }

        return DB::transaction(function () use ($guild, $name, $allowMultipleRoles, $roles) {
            $roleSet = $guild->eventRoleSets()->create([
                'name' => $name,
                'allow_multiple_roles' => $allowMultipleRoles,
            ]);

            foreach ($roles as $index => $role) {
                $roleSet->roles()->create([
                    'name' => trim($role['name']),
                    'discord_role_id' => $role['discord_role_id'] ?? null,
                    'sort_order' => $index,
                    'capacity_mode' => $role['capacity_mode'],
                    'capacity' => $role['capacity_mode'] === EventRole::CAPACITY_UNCAPPED
                        ? null
                        : $role['capacity'],
                ]);
            }

            return $roleSet->load('roles');
        });
    }

    /**
     * @param  array{name: string, capacity_mode: string, capacity: int|null}  $role
     */
    private function assertValidCapacity(array $role): void
    {
        $mode = $role['capacity_mode'] ?? null;
        $capacity = $role['capacity'] ?? null;

        if (! in_array($mode, [EventRole::CAPACITY_UNCAPPED, EventRole::CAPACITY_CAPPED, EventRole::CAPACITY_WAITLISTED], true)) {
            throw new InvalidArgumentException("Invalid capacity mode: {$mode}");
        }

        if ($mode !== EventRole::CAPACITY_UNCAPPED && (! is_int($capacity) || $capacity < 1)) {
            throw new InvalidArgumentException('A capped or waitlisted role requires a positive capacity.');
        }
    }
}
