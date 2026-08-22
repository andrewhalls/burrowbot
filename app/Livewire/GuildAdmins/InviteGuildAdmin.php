<?php

declare(strict_types=1);

namespace App\Livewire\GuildAdmins;

use App\Actions\GuildAdmins\GrantGuildAdminSectionsAction;
use App\Models\DiscordMember;
use App\Models\Guild;
use App\Support\GuildAdmins\GuildAdminSection;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Search picker over the guild's already-synced member directory (reusing
 * `member-directory`'s existing search, unmodified) plus a section
 * checklist, granting the selected member exactly those sections.
 *
 * See openspec specs/guild-admin-permissions - "Granting a section-scoped admin".
 */
class InviteGuildAdmin extends Component
{
    public Guild $guild;

    public string $search = '';

    public ?int $selectedMemberId = null;

    /** @var list<string> */
    public array $sections = [];

    public function mount(Guild $guild): void
    {
        $this->authorize('manageAdmins', $guild);

        $this->guild = $guild;
    }

    public function selectMember(int $memberId): void
    {
        $exists = DiscordMember::query()->where('guild_id', $this->guild->id)->where('id', $memberId)->exists();

        $this->selectedMemberId = $exists ? $memberId : null;
    }

    public function clearSelectedMember(): void
    {
        $this->selectedMemberId = null;
        $this->search = '';
    }

    public function save(GrantGuildAdminSectionsAction $grantSections): void
    {
        $this->authorize('manageAdmins', $this->guild);

        $validated = $this->validate([
            'selectedMemberId' => ['required', 'integer'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['in:'.implode(',', GuildAdminSection::all())],
        ]);

        $member = DiscordMember::query()
            ->where('guild_id', $this->guild->id)
            ->findOrFail($validated['selectedMemberId']);

        $grantSections->execute($this->guild, $member, $validated['sections']);

        $this->dispatch('guild-admin-granted');
    }

    public function render(): View
    {
        $members = $this->selectedMemberId === null && $this->search !== ''
            ? $this->guild->discordMembers()->search($this->search)->limit(10)->get()
            : collect();

        $selectedMember = $this->selectedMemberId
            ? DiscordMember::query()->where('guild_id', $this->guild->id)->find($this->selectedMemberId)
            : null;

        return view('livewire.guild-admins.invite-guild-admin', [
            'members' => $members,
            'selectedMember' => $selectedMember,
            'sectionLabels' => GuildAdminSection::labels(),
        ]);
    }
}
