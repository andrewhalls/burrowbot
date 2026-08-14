<div>
    <input type="text" wire:model.live.debounce.300ms="roleSearch" placeholder="Search roles&hellip;"
           class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm mb-2">

    @if ($this->presetRoleSets->isNotEmpty())
        <div class="mb-2">
            <p class="text-xs text-muted mb-1">Presets</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($this->presetRoleSets as $preset)
                    <button type="button" wire:click="addRoleSetPreset({{ $preset->id }})"
                            title="{{ $preset->roles->pluck('name')->join(', ') }}"
                            class="rounded-pill bg-surface-hover hover:bg-surface-hover px-3 py-1 text-xs">
                        {{ $preset->name }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    @if ($this->roleSearchResults->isNotEmpty())
        <ul class="mb-2 rounded-control border border-line divide-y divide-line">
            @foreach ($this->roleSearchResults as $role)
                <li class="flex items-center justify-between px-3 py-2 text-sm">
                    <span>{{ $role->name }}</span>
                    <button type="button" wire:click="addDiscordRole('{{ $role->discord_role_id }}')" class="text-xs text-accent hover:text-accent">Add</button>
                </li>
            @endforeach
        </ul>
    @endif
</div>
