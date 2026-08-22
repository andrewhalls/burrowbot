<?php

declare(strict_types=1);

namespace App\Livewire\GuildAdmins;

use App\Actions\GuildAdmins\UpdateGuildAdminSectionsAction;
use App\Models\GuildAdmin;
use App\Support\GuildAdmins\GuildAdminSection;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Replaces a granted (scoped) admin's section list.
 *
 * See openspec specs/guild-admin-permissions - "Editing a scoped admin's sections".
 */
class EditGuildAdminSections extends Component
{
    public GuildAdmin $admin;

    /** @var list<string> */
    public array $sections = [];

    public function mount(GuildAdmin $admin): void
    {
        $this->authorize('manageAdmins', $admin->guild);

        $this->admin = $admin;
        $this->sections = $admin->sections ?? [];
    }

    public function save(UpdateGuildAdminSectionsAction $updateSections): void
    {
        $this->authorize('manageAdmins', $this->admin->guild);

        $validated = $this->validate([
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['in:'.implode(',', GuildAdminSection::all())],
        ]);

        $updateSections->execute($this->admin, $validated['sections']);

        $this->dispatch('guild-admin-sections-updated');
    }

    public function render(): View
    {
        return view('livewire.guild-admins.edit-guild-admin-sections', [
            'sectionLabels' => GuildAdminSection::labels(),
        ]);
    }
}
