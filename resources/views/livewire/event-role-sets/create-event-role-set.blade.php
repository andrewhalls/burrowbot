<div class="rounded-card border border-line p-4 space-y-4">
    <div>
        <label class="block text-sm text-muted mb-1">Role set name</label>
        <input type="text" wire:model="name" class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
        @error('name') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" wire:model="allowMultipleRoles">
        Allow a member to hold more than one role at once
    </label>

    <div>
        <label class="block text-sm text-muted mb-1">Roles</label>
        <div class="space-y-2">
            @foreach ($roles as $index => $role)
                <div class="flex gap-2 items-start">
                    <input type="text" wire:model="roles.{{ $index }}.name" placeholder="Role name"
                           class="flex-1 rounded-control bg-surface border border-line px-3 py-2 text-sm">
                    <select wire:model="roles.{{ $index }}.capacity_mode" class="rounded-control bg-surface border border-line px-2 py-2 text-sm">
                        <option value="uncapped">Uncapped</option>
                        <option value="capped">Capped</option>
                        <option value="waitlisted">Capped + waitlist</option>
                    </select>
                    @if (($role['capacity_mode'] ?? 'uncapped') !== 'uncapped')
                        <input type="number" min="1" wire:model="roles.{{ $index }}.capacity" placeholder="Cap"
                               class="w-20 rounded-control bg-surface border border-line px-2 py-2 text-sm">
                    @endif
                    @if (count($roles) > 1)
                        <button type="button" wire:click="removeRoleRow({{ $index }})" class="text-muted hover:text-danger px-2">&times;</button>
                    @endif
                </div>
            @endforeach
        </div>
        @error('roles') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror

        <button type="button" wire:click="addRoleRow" class="mt-2 text-xs text-accent hover:text-accent">+ Add role</button>
    </div>

    <button type="button" wire:click="save" class="rounded-control bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium">
        Create role set
    </button>
</div>
