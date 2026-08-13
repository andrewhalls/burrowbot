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

    <ul class="divide-y divide-line rounded-card border border-line">
        @forelse ($roleSets as $roleSet)
            <li class="p-4">
                <livewire:event-role-sets.manage-event-role-set-roles :role-set="$roleSet" :key="'role-set-roles-'.$roleSet->id" />
            </li>
        @empty
            <li class="p-4 text-sm text-muted">No role sets yet.</li>
        @endforelse
    </ul>
</div>
