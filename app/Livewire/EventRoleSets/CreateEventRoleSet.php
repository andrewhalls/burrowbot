<?php

declare(strict_types=1);

namespace App\Livewire\EventRoleSets;

use App\Actions\EventRoleSets\CreateEventRoleSetAction;
use App\Livewire\Concerns\SearchesDiscordRoles;
use App\Models\DiscordRole;
use App\Models\EventRole;
use App\Models\Guild;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Component;

class CreateEventRoleSet extends Component
{
    use SearchesDiscordRoles;

    public Guild $guild;

    public string $name = '';

    public bool $allowMultipleRoles = false;

    /** @var list<array{name: string, discord_role_id: string, capacity_mode: string, capacity: int|null}> */
    public array $roles = [];

    public function mount(Guild $guild): void
    {
        $this->authorize('manage', $guild);

        $this->guild = $guild;
    }

    public function addDiscordRole(string $discordRoleId): void
    {
        if (in_array($discordRoleId, $this->selectedDiscordRoleIds(), true)) {
            return;
        }

        $role = DiscordRole::query()
            ->where('guild_id', $this->guild->id)
            ->where('discord_role_id', $discordRoleId)
            ->first();

        if (! $role) {
            return;
        }

        $this->roles[] = [
            'name' => $role->name,
            'discord_role_id' => $role->discord_role_id,
            'capacity_mode' => EventRole::CAPACITY_UNCAPPED,
            'capacity' => null,
        ];
    }

    public function addRoleSetPreset(int $roleSetId): void
    {
        $preset = $this->guild->eventRoleSets()->with('roles')->findOrFail($roleSetId);

        foreach ($preset->roles as $role) {
            if ($role->discord_role_id) {
                $this->addDiscordRole($role->discord_role_id);
            }
        }
    }

    public function removeRoleRow(int $index): void
    {
        unset($this->roles[$index]);
        $this->roles = array_values($this->roles);
    }

    public function save(CreateEventRoleSetAction $createRoleSet): void
    {
        $this->authorize('manage', $this->guild);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'roles' => ['array'],
            'roles.*.capacity_mode' => ['required', 'in:'.implode(',', [
                EventRole::CAPACITY_UNCAPPED, EventRole::CAPACITY_CAPPED, EventRole::CAPACITY_WAITLISTED,
            ])],
            'roles.*.capacity' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $createRoleSet->execute($this->guild, $this->name, $this->allowMultipleRoles, $this->roles);
        } catch (InvalidArgumentException $e) {
            $this->addError('roles', $e->getMessage());

            return;
        }

        $this->reset(['name', 'allowMultipleRoles']);
        $this->roles = [];

        $this->dispatch('event-role-set-created');
    }

    protected function guildForRoleSearch(): Guild
    {
        return $this->guild;
    }

    /**
     * @return list<string>
     */
    protected function selectedDiscordRoleIds(): array
    {
        return array_column($this->roles, 'discord_role_id');
    }

    public function render(): View
    {
        return view('livewire.event-role-sets.create-event-role-set');
    }
}
