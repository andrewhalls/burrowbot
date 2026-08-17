<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Event role sets</h2>
        <button type="button" wire:click="toggleCreateForm" class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
            {{ $showCreateForm ? 'Cancel' : '+ New role set' }}
        </button>
    </div>

    <x-list-detail-shell :selected="$selectedRoleSet !== null || $showCreateForm">
        <x-slot:list>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @forelse ($roleSets as $roleSet)
                    <div wire:key="role-set-tile-{{ $roleSet->id }}" wire:click="select({{ $roleSet->id }})"
                         @class([
                            'rounded-card border p-3 cursor-pointer hover:bg-surface-hover transition-colors flex flex-col items-center text-center gap-1',
                            'border-accent' => $selectedRoleSet?->id === $roleSet->id,
                            'border-line' => $selectedRoleSet?->id !== $roleSet->id,
                         ])>
                        <span class="flex h-10 w-10 items-center justify-center rounded-control bg-accent/10 text-accent">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.106A12.318 12.318 0 008.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0112.749 0zM15 19.128v.106A9.38 9.38 0 018.625 21a9.337 9.337 0 01-4.121-.952 4.125 4.125 0 017.533-2.493M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                            </svg>
                        </span>
                        <p class="font-medium text-ink text-sm truncate w-full">{{ $roleSet->name }}</p>
                        <p class="text-xs text-muted">{{ $roleSet->roles_count }} {{ Str::plural('role', $roleSet->roles_count) }}</p>
                    </div>
                @empty
                    <div class="col-span-2">
                        <x-list-detail-empty message="No role sets yet." />
                    </div>
                @endforelse
            </div>
        </x-slot:list>

        <x-slot:detail>
            @if ($showCreateForm)
                <livewire:event-role-sets.create-event-role-set :guild="$guild" :key="'create-role-set-'.$guild->id" />
            @elseif ($selectedRoleSet)
                <livewire:event-role-sets.manage-event-role-set-roles :role-set="$selectedRoleSet" :key="'role-set-detail-'.$selectedRoleSet->id" />
            @endif
        </x-slot:detail>
    </x-list-detail-shell>
</div>
