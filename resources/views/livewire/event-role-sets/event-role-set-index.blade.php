<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Event role sets</h2>
        <button type="button" wire:click="$toggle('showCreateForm')" class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
            {{ $showCreateForm ? 'Cancel' : '+ New role set' }}
        </button>
    </div>

    @if ($showCreateForm)
        <livewire:event-role-sets.create-event-role-set :guild="$guild" :key="'create-role-set-'.$guild->id" />
    @endif

    <x-list-detail-shell :selected="$selectedRoleSet !== null">
        <x-slot:list>
            <div class="space-y-3">
                @forelse ($roleSets as $roleSet)
                    <div wire:key="role-set-tile-{{ $roleSet->id }}" wire:click="select({{ $roleSet->id }})"
                         @class([
                            'rounded-card border p-4 cursor-pointer hover:bg-surface-hover transition-colors',
                            'border-accent' => $selectedRoleSet?->id === $roleSet->id,
                            'border-line' => $selectedRoleSet?->id !== $roleSet->id,
                         ])>
                        <p class="font-medium text-ink truncate">{{ $roleSet->name }}</p>
                        <p class="text-xs text-muted">{{ $roleSet->roles_count }} {{ Str::plural('role', $roleSet->roles_count) }}</p>
                    </div>
                @empty
                    <x-list-detail-empty message="No role sets yet." />
                @endforelse
            </div>
        </x-slot:list>

        <x-slot:detail>
            @if ($selectedRoleSet)
                <livewire:event-role-sets.manage-event-role-set-roles :role-set="$selectedRoleSet" :key="'role-set-detail-'.$selectedRoleSet->id" />
            @endif
        </x-slot:detail>
    </x-list-detail-shell>
</div>
