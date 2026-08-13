<div class="space-y-6">
    <x-guild-nav :guild="$guild" active="themes" />

    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">Themed collections</h2>
        <button type="button" wire:click="$toggle('showCreateForm')" class="text-sm text-indigo-400 hover:text-indigo-300">
            {{ $showCreateForm ? 'Cancel' : '+ New theme' }}
        </button>
    </div>

    @if ($showCreateForm)
        <livewire:collection-themes.create-collection-theme :guild="$guild" :key="'create-theme-'.$guild->id" />
    @endif

    <ul class="divide-y divide-neutral-800 rounded-lg border border-neutral-800">
        @forelse ($themes as $theme)
            <li class="p-4">
                <livewire:collection-themes.manage-collection-theme-items :theme="$theme" :key="'theme-items-'.$theme->id" />
            </li>
        @empty
            <li class="p-4 text-sm text-neutral-500">No themed collections yet.</li>
        @endforelse
    </ul>
</div>
