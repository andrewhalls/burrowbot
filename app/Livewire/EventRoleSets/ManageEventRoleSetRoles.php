<?php

declare(strict_types=1);

namespace App\Livewire\EventRoleSets;

use App\Actions\EventRoleSets\ManageEventRoleSetRolesAction;
use App\Livewire\Concerns\SearchesDiscordRoles;
use App\Models\DiscordRole;
use App\Models\EventRole;
use App\Models\EventRoleSet;
use App\Models\Guild;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Component;

class ManageEventRoleSetRoles extends Component
{
    use SearchesDiscordRoles;

    public EventRoleSet $roleSet;

    public string $newRoleCapacityMode = EventRole::CAPACITY_UNCAPPED;

    public ?int $newRoleCapacity = null;

    public function mount(EventRoleSet $roleSet): void
    {
        $this->authorize('manage', $roleSet);

        $this->roleSet = $roleSet;
    }

    public function addDiscordRole(string $discordRoleId, ManageEventRoleSetRolesAction $manageRoles): void
    {
        $this->authorize('manage', $this->roleSet);

        if (in_array($discordRoleId, $this->selectedDiscordRoleIds(), true)) {
            return;
        }

        $role = DiscordRole::query()
            ->where('guild_id', $this->roleSet->guild_id)
            ->where('discord_role_id', $discordRoleId)
            ->first();

        if (! $role) {
            return;
        }

        $this->validate([
            'newRoleCapacityMode' => ['required', 'in:'.implode(',', [
                EventRole::CAPACITY_UNCAPPED, EventRole::CAPACITY_CAPPED, EventRole::CAPACITY_WAITLISTED,
            ])],
            'newRoleCapacity' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $manageRoles->addRole($this->roleSet, $role->name, $role->discord_role_id, $this->newRoleCapacityMode, $this->newRoleCapacity);
        } catch (InvalidArgumentException $e) {
            $this->addError('newRoleCapacityMode', $e->getMessage());

            return;
        }

        $this->roleSet->unsetRelation('roles');
    }

    /**
     * Bulk-adds every role from an existing role set preset, each using
     * this form's currently-selected capacity settings (design.md -
     * Non-goals: no per-role capacity during a bulk add).
     */
    public function addRoleSetPreset(int $roleSetId, ManageEventRoleSetRolesAction $manageRoles): void
    {
        $this->authorize('manage', $this->roleSet);

        $preset = EventRoleSet::query()
            ->where('guild_id', $this->roleSet->guild_id)
            ->with('roles')
            ->findOrFail($roleSetId);

        foreach ($preset->roles as $role) {
            if ($role->discord_role_id) {
                $this->addDiscordRole($role->discord_role_id, $manageRoles);
            }
        }
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
            $this->addError('newRoleCapacityMode', $e->getMessage());

            return;
        }

        $this->roleSet->unsetRelation('roles');
    }

    protected function guildForRoleSearch(): Guild
    {
        return $this->roleSet->guild;
    }

    protected function excludedPresetRoleSetId(): ?int
    {
        return $this->roleSet->id;
    }

    /**
     * @return list<string>
     */
    protected function selectedDiscordRoleIds(): array
    {
        return $this->roleSet->roles->pluck('discord_role_id')->filter()->values()->all();
    }

    public function render(): View
    {
        return view('livewire.event-role-sets.manage-event-role-set-roles', [
            'roles' => $this->roleSet->roles,
            'editable' => $this->roleSet->isEditable(),
        ]);
    }
}
