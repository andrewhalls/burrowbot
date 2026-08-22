<div class="space-y-6">
    <div class="flex items-center justify-between gap-2">
        <h2 class="text-lg font-semibold">Admins</h2>
        <button type="button" wire:click="toggleInviteForm" class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
            {{ $showInviteForm ? 'Cancel' : '+ Invite admin' }}
        </button>
    </div>

    @error('revoke') <p class="text-xs text-danger">{{ $message }}</p> @enderror

    @if ($showInviteForm)
        <livewire:guild-admins.invite-guild-admin :guild="$guild" :key="'invite-admin-'.$guild->id" />
    @endif

    <div class="rounded-card border border-line divide-y divide-line max-w-2xl">
        @forelse ($admins as $admin)
            <div class="p-4" wire:key="admin-row-{{ $admin->id }}">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-medium text-ink text-sm truncate">{{ $admin->user?->name ?? 'Pending invite' }}</p>
                        @if ($admin->source === 'discord_sync')
                            <p class="text-xs text-muted mt-1">Full admin &middot; via Discord</p>
                        @else
                            <div class="flex items-center gap-1 flex-wrap mt-1">
                                @foreach (($admin->sections ?? []) as $section)
                                    <span class="rounded-pill bg-surface-hover px-2 py-0.5 text-[11px] text-muted">{{ $sectionLabels[$section] ?? $section }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if ($admin->source === 'granted')
                        <div class="flex gap-2 text-xs shrink-0">
                            <button type="button" wire:click="startEditing({{ $admin->id }})" class="text-accent hover:text-accent">Edit</button>
                            <button type="button" wire:click="revoke({{ $admin->id }})" wire:confirm="Revoke this admin's access?" class="text-danger hover:text-danger">Revoke</button>
                        </div>
                    @endif
                </div>

                @if ($editingAdminId === $admin->id)
                    <div class="mt-3">
                        <livewire:guild-admins.edit-guild-admin-sections :admin="$admin" :key="'edit-admin-'.$admin->id" />
                    </div>
                @endif
            </div>
        @empty
            <x-list-detail-empty message="No admins yet." />
        @endforelse
    </div>
</div>
