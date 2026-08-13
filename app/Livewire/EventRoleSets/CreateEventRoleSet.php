<?php

declare(strict_types=1);

namespace App\Livewire\EventRoleSets;

use App\Actions\EventRoleSets\CreateEventRoleSetAction;
use App\Models\EventRole;
use App\Models\Guild;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Component;

class CreateEventRoleSet extends Component
{
    public Guild $guild;

    public string $name = '';

    public bool $allowMultipleRoles = false;

    /** @var list<array{name: string, capacity_mode: string, capacity: int|null}> */
    public array $roles = [];

    public function mount(Guild $guild): void
    {
        $this->authorize('manage', $guild);

        $this->guild = $guild;
        $this->roles = $this->blankRoles();
    }

    public function addRoleRow(): void
    {
        $this->roles[] = ['name' => '', 'capacity_mode' => EventRole::CAPACITY_UNCAPPED, 'capacity' => null];
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
        $this->roles = $this->blankRoles();

        $this->dispatch('event-role-set-created');
    }

    /**
     * @return list<array{name: string, capacity_mode: string, capacity: int|null}>
     */
    private function blankRoles(): array
    {
        return [
            ['name' => '', 'capacity_mode' => EventRole::CAPACITY_UNCAPPED, 'capacity' => null],
            ['name' => '', 'capacity_mode' => EventRole::CAPACITY_UNCAPPED, 'capacity' => null],
        ];
    }

    public function render(): View
    {
        return view('livewire.event-role-sets.create-event-role-set');
    }
}
