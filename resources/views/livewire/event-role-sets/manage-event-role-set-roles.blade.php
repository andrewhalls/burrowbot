<div>
    <div class="flex items-center justify-between mb-2">
        <h3 class="font-medium">{{ $roleSet->name }}</h3>
        @unless ($editable)
            <span class="text-xs text-warning">Locked while an open occurrence uses this role set</span>
        @endunless
    </div>

    <ul class="flex flex-wrap gap-2 mb-3">
        @foreach ($roles as $role)
            <li class="inline-flex items-center gap-1 rounded-full bg-surface-hover px-3 py-1 text-xs">
                {{ $role->name }}
                @if ($role->capacity_mode !== 'uncapped')
                    <span class="text-muted">({{ $role->capacity }}{{ $role->capacity_mode === 'waitlisted' ? '+wl' : '' }})</span>
                @endif
                @if ($editable)
                    <button type="button" wire:click="removeRole({{ $role->id }})" wire:confirm="Remove this role?" class="text-muted hover:text-danger">&times;</button>
                @endif
            </li>
        @endforeach
    </ul>

    @if ($editable)
        <div class="flex gap-2 mb-2">
            <select wire:model="newRoleCapacityMode" class="rounded-control bg-surface border border-line px-2 py-1.5 text-sm">
                <option value="uncapped">Uncapped</option>
                <option value="capped">Capped</option>
                <option value="waitlisted">Capped + waitlist</option>
            </select>
            @if ($newRoleCapacityMode !== 'uncapped')
                <input type="number" min="1" wire:model="newRoleCapacity" placeholder="Cap"
                       class="w-20 rounded-control bg-surface border border-line px-2 py-1.5 text-sm">
            @endif
        </div>
        @error('newRoleCapacityMode') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror

        @include('partials.discord-role-search-results')
    @endif
</div>
