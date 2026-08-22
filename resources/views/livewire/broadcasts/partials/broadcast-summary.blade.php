{{-- Read-only summary for the list-detail panel - broadcasts have no
     per-occurrence interactive content (no roster/entrants), so a recent-
     occurrences list is the full detail content needed, mirroring the
     Events summary partial. --}}
<div class="rounded-card border border-line p-4 space-y-4">
    <div>
        <h3 class="text-lg font-semibold text-ink">{{ $broadcast->title }}</h3>
        @if ($broadcast->creator)
            <p class="text-xs text-muted mt-1">Created by {{ $broadcast->creator->name }}</p>
        @endif
        <p class="text-sm text-muted mt-1 whitespace-pre-line">{{ $broadcast->message_template }}</p>
    </div>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div>
            <dt class="text-xs text-muted">Schedule</dt>
            <dd class="text-ink">{{ $broadcast->isRecurring() ? 'Recurring' : 'One-off' }}</dd>
        </div>
        <div>
            <dt class="text-xs text-muted">Status</dt>
            <dd class="text-ink">{{ ucfirst($broadcast->status) }}</dd>
        </div>
    </dl>

    <div>
        <p class="text-xs text-muted mb-2">Recent occurrences</p>
        <ul class="divide-y divide-line rounded-control border border-line">
            @forelse ($broadcast->occurrences as $occurrence)
                <li class="p-3 text-sm flex items-center justify-between">
                    <x-local-time :at="$occurrence->scheduled_post_at" />
                    <span class="text-muted">{{ ucfirst($occurrence->status) }}</span>
                </li>
            @empty
                <li class="p-3 text-sm text-muted">No occurrences generated yet.</li>
            @endforelse
        </ul>
    </div>
</div>
