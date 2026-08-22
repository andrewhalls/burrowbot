<div class="rounded-card border border-line p-4 space-y-4 max-w-xl">
    @if (! $selectedMember)
        <div>
            <label class="block text-sm text-muted mb-1">Search members</label>
            <input type="text" wire:model.live.debounce.300ms="search" autocomplete="off"
                   placeholder="Search by username or Discord ID&hellip;"
                   class="w-full rounded-control bg-surface border border-line px-3 py-2 text-sm">
            @error('selectedMemberId') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror

            @if ($search !== '')
                <ul class="mt-2 divide-y divide-line rounded-control border border-line max-h-56 overflow-auto">
                    @forelse ($members as $member)
                        <li wire:key="member-option-{{ $member->id }}" wire:click="selectMember({{ $member->id }})"
                            class="px-3 py-2 text-sm cursor-pointer hover:bg-surface-hover">
                            {{ $member->display_name_or_username }}
                        </li>
                    @empty
                        <li class="px-3 py-2 text-sm text-muted">No synced members match.</li>
                    @endforelse
                </ul>
            @endif
        </div>
    @else
        <div class="flex items-center justify-between gap-2">
            <p class="text-sm text-ink">Inviting <span class="font-medium">{{ $selectedMember->display_name_or_username }}</span></p>
            <button type="button" wire:click="clearSelectedMember" class="text-xs text-muted hover:text-ink">Change</button>
        </div>

        <div>
            <label class="block text-sm text-muted mb-2">Sections to grant</label>
            <div class="space-y-1">
                @foreach ($sectionLabels as $key => $label)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="sections" value="{{ $key }}">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            @error('sections') <p class="text-danger text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="button" wire:click="save" class="rounded-control bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
            Grant access
        </button>
    @endif
</div>
