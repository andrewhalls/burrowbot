<div class="max-w-5xl mx-auto w-full px-6 py-10">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-xl font-semibold text-ink">Burrow</h1>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-muted hover:text-ink">Sign out</button>
        </form>
    </div>

    <p class="text-muted mb-8">Signed in as {{ auth()->user()->name }}.</p>

    @if ($guilds->isEmpty())
        <div class="rounded-card border border-line p-6 space-y-4">
            <h2 class="font-semibold text-ink">Get started</h2>
            <p class="text-sm text-muted">
                You're signed in, but Burrow doesn't see any Discord servers you administer yet.
                To fix that:
            </p>
            <ol class="list-decimal list-inside text-sm text-muted space-y-1">
                <li>Invite the bot to your Discord server using the button below.</li>
                <li>Make sure your Discord account has the "Manage Server" permission on that server.</li>
                <li>Come back here and click "Check again".</li>
            </ol>
            <div class="flex items-center gap-4 pt-2">
                <a href="{{ \App\Support\Discord\BotInviteUrl::build() }}"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center rounded-pill bg-accent hover:bg-accent-hover px-5 py-2.5 text-sm font-medium text-accent-ink">
                    Invite bot to your server
                </a>
                <a href="{{ route('auth.discord.redirect') }}" class="text-sm text-muted hover:text-ink">
                    Check again
                </a>
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($guilds as $guild)
                <div class="rounded-card border border-line p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-control bg-accent text-accent-ink font-semibold">
                            {{ Str::upper(Str::substr($guild->name, 0, 1)) }}
                        </span>
                        <h2 class="font-semibold text-ink">{{ $guild->name }}</h2>
                    </div>
                    <div class="flex flex-wrap gap-2 text-sm">
                        <a href="{{ route('guilds.settings', $guild) }}" class="rounded-pill border border-line px-3 py-1.5 text-muted hover:bg-surface-hover hover:text-ink">Settings</a>
                        <a href="{{ route('guilds.themes.index', $guild) }}" class="rounded-pill border border-line px-3 py-1.5 text-muted hover:bg-surface-hover hover:text-ink">Collection themes</a>
                        <a href="{{ route('guilds.event-role-sets.index', $guild) }}" class="rounded-pill border border-line px-3 py-1.5 text-muted hover:bg-surface-hover hover:text-ink">Event role sets</a>
                        <a href="{{ route('guilds.events.index', $guild) }}" class="rounded-pill border border-line px-3 py-1.5 text-muted hover:bg-surface-hover hover:text-ink">Events</a>
                        <a href="{{ route('guilds.giveaways.index', $guild) }}" class="rounded-pill border border-line px-3 py-1.5 text-muted hover:bg-surface-hover hover:text-ink">Popup giveaways</a>
                        <a href="{{ route('guilds.standard-giveaways.index', $guild) }}" class="rounded-pill border border-line px-3 py-1.5 text-muted hover:bg-surface-hover hover:text-ink">Standard giveaways</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
