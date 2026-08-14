<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\DiscordRole;
use App\Models\Guild;
use Illuminate\Support\Collection;

/**
 * Gives a Livewire component a searchable-multi-select-with-presets
 * contract over a guild's synced Discord roles: a search box (individual
 * roles) plus the guild's existing Event Role Sets shown as one-click
 * presets. What "adding a role" actually does differs per call site
 * (a flat ID list vs. a capacity-bearing EventRole row), so only the
 * search/presets half is shared here - see openspec design.md
 * (add-discord-role-picker) Decision 1.
 */
trait SearchesDiscordRoles
{
    public string $roleSearch = '';

    /**
     * @return Collection<int, DiscordRole>
     */
    public function getRoleSearchResultsProperty(): Collection
    {
        if ($this->roleSearch === '') {
            return collect();
        }

        return DiscordRole::query()
            ->where('guild_id', $this->guildForRoleSearch()->id)
            ->whereLike('name', "%{$this->roleSearch}%")
            ->whereNotIn('discord_role_id', $this->selectedDiscordRoleIds())
            ->limit(10)
            ->get();
    }

    /**
     * @return Collection<int, \App\Models\EventRoleSet>
     */
    public function getPresetRoleSetsProperty(): Collection
    {
        return $this->guildForRoleSearch()->eventRoleSets()
            ->when($this->excludedPresetRoleSetId(), fn ($query, $id) => $query->where('id', '!=', $id))
            ->with('roles')
            ->orderBy('name')
            ->get();
    }

    /**
     * A role set can't sensibly be offered as a preset for itself.
     * Overridden by ManageEventRoleSetRoles; CreateStandardGiveaway and
     * CreateEventRoleSet have no "current" role set, so nothing to exclude.
     */
    protected function excludedPresetRoleSetId(): ?int
    {
        return null;
    }

    abstract protected function guildForRoleSearch(): Guild;

    /**
     * @return list<string>
     */
    abstract protected function selectedDiscordRoleIds(): array;
}
