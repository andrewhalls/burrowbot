<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-ink">Popup giveaways</h2>
        <button type="button" wire:click="$toggle('showCreateForm')" class="rounded-pill bg-accent hover:bg-accent-hover px-4 py-2 text-sm font-medium text-accent-ink">
            {{ $showCreateForm ? 'Cancel' : '+ New giveaway' }}
        </button>
    </div>

    @if ($showCreateForm)
        <livewire:giveaways.create-giveaway :guild="$guild" :key="'create-giveaway-'.$guild->id" />
    @endif

    <ul class="divide-y divide-line rounded-card border border-line">
        @forelse ($giveaways as $giveaway)
            <li class="p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div>
                        <p class="font-medium text-ink">{{ $giveaway->collectionTheme->name }} giveaway</p>
                        <p class="text-xs text-muted">
                            {{ $giveaway->entries_count }} {{ Str::plural('entrant', $giveaway->entries_count) }} &middot;
                            {{ $giveaway->duration_minutes }} min
                            @if ($giveaway->scheduled_start_at)
                                &middot; Scheduled for {{ $giveaway->scheduled_start_at->format('M j, g:ia') }}
                            @endif
                        </p>
                    </div>
                    <span @class([
                        'rounded-pill px-2.5 py-1 text-xs font-medium',
                        'bg-surface-hover text-muted' => $giveaway->status === 'draft',
                        'bg-success/15 text-success' => $giveaway->status === 'active',
                        'bg-line text-muted' => $giveaway->status === 'closed',
                    ])>{{ ucfirst($giveaway->status) }}</span>
                </div>
                <div class="flex items-center gap-3 text-xs">
                    @if ($giveaway->status === 'draft')
                        <button type="button" wire:click="start({{ $giveaway->id }})" wire:confirm="Start this popup giveaway now?" class="text-success hover:text-success font-medium">
                            Start
                        </button>
                    @endif
                    <a href="{{ route('guilds.giveaways.show', [$guild, $giveaway]) }}" class="text-muted hover:text-ink">Manage</a>
                </div>
            </li>
        @empty
            <li class="p-4 text-sm text-muted">No popup giveaways yet.</li>
        @endforelse
    </ul>
</div>
