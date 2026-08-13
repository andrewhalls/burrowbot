<?php

declare(strict_types=1);

namespace App\Livewire\EventRoleSets;

use App\Actions\EventRoleSets\ManageEventRoleSetRolesAction;
use App\Models\EventRole;
use App\Models\EventRoleSet;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Component;

class ManageEventRoleSetRoles extends Component
{
    public EventRoleSet $roleSet;

    public string $newRoleName = '';

    public string $newRoleCapacityMode = EventRole::CAPACITY_UNCAPPED;

    public ?int $newRoleCapacity = null;

    public function mount(EventRoleSet $roleSet): void
    {
        $this->authorize('manage', $roleSet);

        $this->roleSet = $roleSet;
    }

    public function addRole(ManageEventRoleSetRolesAction $manageRoles): void
    {
        $this->authorize('manage', $this->roleSet);

        $this->validate([
            'newRoleName' => ['required', 'string', 'max:255'],
            'newRoleCapacityMode' => ['required', 'in:'.implode(',', [
                EventRole::CAPACITY_UNCAPPED, EventRole::CAPACITY_CAPPED, EventRole::CAPACITY_WAITLISTED,
            ])],
            'newRoleCapacity' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $manageRoles->addRole($this->roleSet, $this->newRoleName, $this->newRoleCapacityMode, $this->newRoleCapacity);
        } catch (InvalidArgumentException $e) {
            $this->addError('newRoleName', $e->getMessage());

            return;
        }

        $this->reset(['newRoleName', 'newRoleCapacityMode', 'newRoleCapacity']);
        $this->newRoleCapacityMode = EventRole::CAPACITY_UNCAPPED;
        $this->roleSet->unsetRelation('roles');
    }

    public function removeRole(int $roleId, ManageEventRoleSetRolesAction $manageRoles): void
    {
        $this->authorize('manage', $this->roleSet);

        $role = EventRole::query()
            ->where('event_role_set_id', $this->roleSet->id)
            ->findOrFail($roleId);

        try {
            $manageRoles->removeRole($this->roleSet, $role);
        } catch (InvalidArgumentException $e) {
            $this->addError('newRoleName', $e->getMessage());

            return;
        }

        $this->roleSet->unsetRelation('roles');
    }

    public function render(): View
    {
        return view('livewire.event-role-sets.manage-event-role-set-roles', [
            'roles' => $this->roleSet->roles,
            'editable' => $this->roleSet->isEditable(),
        ]);
    }
}
