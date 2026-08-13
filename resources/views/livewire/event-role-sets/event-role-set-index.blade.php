<div class="space-y-6">
    <x-guild-nav :guild="$guild" active="event-role-sets" />

    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Event role sets</h2>
        <button type="button" wire:click="$toggle('showCreateForm')" class="text-sm text-indigo-400 hover:text-indigo-300">
            {{ $showCreateForm ? 'Cancel' : '+ New role set' }}
        </button>
    </div>

    @if ($showCreateForm)
        <livewire:event-role-sets.create-event-role-set :guild="$guild" :key="'create-role-set-'.$guild->id" />
    @endif

    <ul class="divide-y divide-neutral-800 rounded-lg border border-neutral-800">
        @forelse ($roleSets as $roleSet)
            <li class="p-4">
                <livewire:event-role-sets.manage-event-role-set-roles :role-set="$roleSet" :key="'role-set-roles-'.$roleSet->id" />
            </li>
        @empty
            <li class="p-4 text-sm text-neutral-500">No role sets yet.</li>
        @endforelse
    </ul>
</div>
