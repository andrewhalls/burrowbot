<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">{{ $occurrence->title }}</h2>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by member&hellip;"
               class="rounded-control bg-surface border border-line px-3 py-2 text-sm">
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($roles as $entry)
            <div class="rounded-card border border-line p-4">
                <h3 class="font-medium mb-2">
                    {{ $entry['role']->name }}
                    <span class="text-xs text-muted">
                        ({{ $entry['confirmed']->count() }}{{ $entry['role']->capacity ? '/'.$entry['role']->capacity : '' }})
                    </span>
                </h3>

                <ul class="divide-y divide-line text-sm mb-2">
                    @forelse ($entry['confirmed'] as $signup)
                        <li class="py-1.5 text-ink">{{ $signup->discordMember->display_name_or_username }}</li>
                    @empty
                        <li class="py-1.5 text-muted">No one yet.</li>
                    @endforelse
                </ul>

                @if ($entry['waitlisted']->isNotEmpty())
                    <p class="text-xs text-warning font-medium mb-1">Waitlist</p>
                    <ul class="space-y-1">
                        @foreach ($entry['waitlisted'] as $signup)
                            <li><span class="rounded-pill bg-warning/15 text-warning px-2 py-0.5 text-xs">{{ $signup->discordMember->display_name_or_username }}</span></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>

    <div class="rounded-card border border-line p-4">
        <h3 class="font-medium mb-2">Not attending</h3>
        <ul class="divide-y divide-line text-sm">
            @forelse ($notAttending as $attendance)
                <li class="py-1.5 text-muted">{{ $attendance->discordMember->display_name_or_username }}</li>
            @empty
                <li class="py-1.5 text-muted">No one has said they can't make it.</li>
            @endforelse
        </ul>
    </div>
</div>
