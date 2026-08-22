<?php

declare(strict_types=1);

namespace App\Livewire\GuildAdmins;

use App\Actions\GuildAdmins\RevokeGuildAdminAction;
use App\Models\Guild;
use App\Models\GuildAdmin;
use App\Support\GuildAdmins\GuildAdminSection;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Lists a guild's admins (both tiers) and lets a full admin invite a new
 * scoped admin, edit an existing scoped admin's sections, or revoke a
 * scoped admin. Restricted entirely to full (Discord-synced) admins via
 * GuildPolicy::manageAdmins - a scoped admin cannot reach this screen
 * regardless of which sections they hold.
 *
 * See openspec specs/guild-admin-permissions - "Admin management
 * restricted to full admins".
 */
class AdminIndex extends Component
{
    public Guild $guild;

    public bool $showInviteForm = false;

    public ?int $editingAdminId = null;

    public function mount(Guild $guild): void
    {
        $this->authorize('manageAdmins', $guild);

        $this->guild = $guild;
    }

    public function toggleInviteForm(): void
    {
        $this->authorize('manageAdmins', $this->guild);

        $this->showInviteForm = ! $this->showInviteForm;

        if ($this->showInviteForm) {
            $this->editingAdminId = null;
        }
    }

    #[On('guild-admin-granted')]
    public function closeInviteForm(): void
    {
        $this->showInviteForm = false;
    }

    public function startEditing(int $adminId): void
    {
        $this->authorize('manageAdmins', $this->guild);

        $exists = GuildAdmin::query()
            ->where('guild_id', $this->guild->id)
            ->where('id', $adminId)
            ->where('source', GuildAdmin::SOURCE_GRANTED)
            ->exists();

        $this->editingAdminId = $exists ? $adminId : null;
        $this->showInviteForm = false;
    }

    public function cancelEditing(): void
    {
        $this->editingAdminId = null;
    }

    #[On('guild-admin-sections-updated')]
    public function closeEditForm(): void
    {
        $this->editingAdminId = null;
    }

    public function revoke(int $adminId, RevokeGuildAdminAction $revokeAdmin): void
    {
        $this->authorize('manageAdmins', $this->guild);

        $admin = GuildAdmin::query()->where('guild_id', $this->guild->id)->findOrFail($adminId);

        try {
            $revokeAdmin->execute($admin);
        } catch (InvalidArgumentException $e) {
            $this->addError('revoke', $e->getMessage());

            return;
        }

        if ($this->editingAdminId === $adminId) {
            $this->editingAdminId = null;
        }
    }

    public function render(): View
    {
        $admins = $this->guild->guildAdmins()
            ->with('user')
            ->orderByDesc('source')
            ->orderBy('created_at')
            ->get();

        return view('livewire.guild-admins.admin-index', [
            'admins' => $admins,
            'sectionLabels' => GuildAdminSection::labels(),
        ]);
    }
}
