<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">{{ $occurrence->title }}</h2>
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by member&hellip;"
               class="rounded bg-neutral-900 border border-neutral-700 px-3 py-2 text-sm">
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($roles as $entry)
            <div class="rounded-lg border border-neutral-800 p-4">
                <h3 class="font-medium mb-2">
                    {{ $entry['role']->name }}
                    <span class="text-xs text-neutral-500">
                        ({{ $entry['confirmed']->count() }}{{ $entry['role']->capacity ? '/'.$entry['role']->capacity : '' }})
                    </span>
                </h3>

                <ul class="space-y-1 text-sm mb-2">
                    @forelse ($entry['confirmed'] as $signup)
                        <li>{{ $signup->discordMember->username }}</li>
                    @empty
                        <li class="text-neutral-500">No one yet.</li>
                    @endforelse
                </ul>

                @if ($entry['waitlisted']->isNotEmpty())
                    <p class="text-xs text-amber-400 mb-1">Waitlist</p>
                    <ul class="space-y-1 text-sm text-neutral-400">
                        @foreach ($entry['waitlisted'] as $signup)
                            <li>{{ $signup->discordMember->username }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </div>

    <div class="rounded-lg border border-neutral-800 p-4">
        <h3 class="font-medium mb-2">Not attending</h3>
        <ul class="space-y-1 text-sm">
            @forelse ($notAttending as $attendance)
                <li>{{ $attendance->discordMember->username }}</li>
            @empty
                <li class="text-neutral-500">No one has said they can't make it.</li>
            @endforelse
        </ul>
    </div>
</div>
