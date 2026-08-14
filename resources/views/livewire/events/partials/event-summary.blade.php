{{-- Lightweight read-only summary for the list-detail panel - Events has no
     dedicated per-event dashboard component (unlike GiveawayDashboard/
     OccurrenceDashboard), only per-occurrence rosters, so this is the
     minimum content needed to avoid an always-empty detail panel
     (design.md Decision 2, add-dashboard-list-detail-layout). --}}
<div class="rounded-card border border-line p-4 space-y-4">
    <div>
        <h3 class="text-lg font-semibold text-ink">{{ $event->title }}</h3>
        @if ($event->description)
            <p class="text-sm text-muted mt-1">{{ $event->description }}</p>
        @endif
    </div>

    <dl class="grid grid-cols-2 gap-3 text-sm">
        <div>
            <dt class="text-xs text-muted">Role set</dt>
            <dd class="text-ink">{{ $event->eventRoleSet->name }}</dd>
        </div>
        <div>
            <dt class="text-xs text-muted">Schedule</dt>
            <dd class="text-ink">{{ $event->isRecurring() ? 'Recurring' : 'One-off' }}</dd>
        </div>
        <div>
            <dt class="text-xs text-muted">Posting mode</dt>
            <dd class="text-ink">{{ $event->posting_mode === 'thread' ? 'New thread per occurrence' : 'New message per occurrence' }}</dd>
        </div>
        <div>
            <dt class="text-xs text-muted">Status</dt>
            <dd class="text-ink">{{ ucfirst($event->status) }}</dd>
        </div>
    </dl>

    <div>
        <p class="text-xs text-muted mb-2">Recent occurrences</p>
        <ul class="divide-y divide-line rounded-control border border-line">
            @forelse ($event->occurrences as $occurrence)
                <li class="p-3 text-sm flex items-center justify-between">
                    <span>
                        <x-local-time :at="$occurrence->scheduled_start_at" />
                        <span class="text-muted">&middot; {{ ucfirst($occurrence->status) }}</span>
                    </span>
                    <a href="{{ route('guilds.event-occurrences.show', [$event->guild, $occurrence]) }}" class="text-accent hover:text-accent text-xs">View roster</a>
                </li>
            @empty
                <li class="p-3 text-sm text-muted">No occurrences generated yet.</li>
            @endforelse
        </ul>
    </div>
</div>
