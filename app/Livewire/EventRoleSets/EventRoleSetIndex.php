<?php

declare(strict_types=1);

namespace App\Livewire\EventRoleSets;

use App\Models\Guild;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class EventRoleSetIndex extends Component
{
    public Guild $guild;

    public bool $showCreateForm = false;

    public function mount(Guild $guild): void
    {
        $this->authorize('view', $guild);

        $this->guild = $guild;
    }

    #[On('event-role-set-created')]
    public function closeCreateForm(): void
    {
        $this->showCreateForm = false;
    }

    public function render(): View
    {
        $roleSets = $this->guild->eventRoleSets()
            ->withCount('roles')
            ->orderBy('name')
            ->get();

        return view('livewire.event-role-sets.event-role-set-index', [
            'roleSets' => $roleSets,
        ]);
    }
}
