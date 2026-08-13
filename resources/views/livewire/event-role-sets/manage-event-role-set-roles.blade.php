<div>
    <div class="flex items-center justify-between mb-2">
        <h3 class="font-medium">{{ $roleSet->name }}</h3>
        @unless ($editable)
            <span class="text-xs text-amber-400">Locked while an open occurrence uses this role set</span>
        @endunless
    </div>

    <ul class="flex flex-wrap gap-2 mb-3">
        @foreach ($roles as $role)
            <li class="inline-flex items-center gap-1 rounded-full bg-neutral-800 px-3 py-1 text-xs">
                {{ $role->name }}
                @if ($role->capacity_mode !== 'uncapped')
                    <span class="text-neutral-500">({{ $role->capacity }}{{ $role->capacity_mode === 'waitlisted' ? '+wl' : '' }})</span>
                @endif
                @if ($editable)
                    <button type="button" wire:click="removeRole({{ $role->id }})" wire:confirm="Remove this role?" class="text-neutral-500 hover:text-red-400">&times;</button>
                @endif
            </li>
        @endforeach
    </ul>

    @if ($editable)
        <div class="flex gap-2">
            <input type="text" wire:model="newRoleName" placeholder="New role"
                   class="flex-1 rounded bg-neutral-900 border border-neutral-700 px-3 py-1.5 text-sm">
            <select wire:model="newRoleCapacityMode" class="rounded bg-neutral-900 border border-neutral-700 px-2 py-1.5 text-sm">
                <option value="uncapped">Uncapped</option>
                <option value="capped">Capped</option>
                <option value="waitlisted">Capped + waitlist</option>
            </select>
            @if ($newRoleCapacityMode !== 'uncapped')
                <input type="number" min="1" wire:model="newRoleCapacity" placeholder="Cap"
                       class="w-20 rounded bg-neutral-900 border border-neutral-700 px-2 py-1.5 text-sm">
            @endif
            <button type="button" wire:click="addRole" class="rounded bg-neutral-800 hover:bg-neutral-700 px-3 py-1.5 text-sm">Add</button>
        </div>
        @error('newRoleName') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    @endif
</div>
