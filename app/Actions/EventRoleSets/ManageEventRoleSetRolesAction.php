<?php

declare(strict_types=1);

namespace App\Actions\EventRoleSets;

use App\Models\EventRole;
use App\Models\EventRoleSet;
use InvalidArgumentException;

/**
 * Adds/removes/reconfigures roles on an existing role set. Blocked while
 * the role set backs an occurrence that is posted and not yet started.
 *
 * See openspec specs/event-role-sets - "Role set item management".
 */
class ManageEventRoleSetRolesAction
{
    public function addRole(EventRoleSet $roleSet, string $name, string $capacityMode, ?int $capacity): EventRole
    {
        $this->ensureEditable($roleSet);
        $this->assertValidCapacity($capacityMode, $capacity);

        $name = trim($name);

        if ($name === '') {
            throw new InvalidArgumentException('Role name cannot be blank.');
        }

        $nextSortOrder = ((int) $roleSet->roles()->max('sort_order')) + 1;

        return $roleSet->roles()->create([
            'name' => $name,
            'sort_order' => $nextSortOrder,
            'capacity_mode' => $capacityMode,
            'capacity' => $capacityMode === EventRole::CAPACITY_UNCAPPED ? null : $capacity,
        ]);
    }

    public function removeRole(EventRoleSet $roleSet, EventRole $role): void
    {
        $this->ensureEditable($roleSet);

        $role->delete();
    }

    private function ensureEditable(EventRoleSet $roleSet): void
    {
        if (! $roleSet->isEditable()) {
            throw new InvalidArgumentException('This role set cannot be edited while an open occurrence uses it.');
        }
    }

    private function assertValidCapacity(string $mode, ?int $capacity): void
    {
        if (! in_array($mode, [EventRole::CAPACITY_UNCAPPED, EventRole::CAPACITY_CAPPED, EventRole::CAPACITY_WAITLISTED], true)) {
            throw new InvalidArgumentException("Invalid capacity mode: {$mode}");
        }

        if ($mode !== EventRole::CAPACITY_UNCAPPED && (! is_int($capacity) || $capacity < 1)) {
            throw new InvalidArgumentException('A capped or waitlisted role requires a positive capacity.');
        }
    }
}
