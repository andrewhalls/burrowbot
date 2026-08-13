<div class="max-w-5xl mx-auto w-full px-6 py-10">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-xl font-semibold">Burrow</h1>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-neutral-400 hover:text-neutral-100">Sign out</button>
        </form>
    </div>

    <p class="text-neutral-400 mb-8">Signed in as {{ auth()->user()->name }}.</p>

    @if ($guilds->isEmpty())
        <div class="rounded-lg border border-neutral-800 p-6 space-y-4">
            <h2 class="font-medium">Get started</h2>
            <p class="text-sm text-neutral-400">
                You're signed in, but Burrow doesn't see any Discord servers you administer yet.
                To fix that:
            </p>
            <ol class="list-decimal list-inside text-sm text-neutral-400 space-y-1">
                <li>Invite the bot to your Discord server using the button below.</li>
                <li>Make sure your Discord account has the "Manage Server" permission on that server.</li>
                <li>Come back here and click "Check again".</li>
            </ol>
            <div class="flex items-center gap-4 pt-2">
                <a href="{{ \App\Support\Discord\BotInviteUrl::build() }}"
                   target="_blank" rel="noopener"
                   class="inline-flex items-center rounded bg-indigo-600 hover:bg-indigo-500 px-4 py-2 text-sm font-medium text-white">
                    Invite bot to your server
                </a>
                <a href="{{ route('auth.discord.redirect') }}" class="text-sm text-neutral-400 hover:text-neutral-100">
                    Check again
                </a>
            </div>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($guilds as $guild)
                <div class="rounded-lg border border-neutral-800 p-4">
                    <h2 class="font-medium mb-3">{{ $guild->name }}</h2>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                        <a href="{{ route('guilds.settings', $guild) }}" class="text-neutral-400 hover:text-neutral-100">Settings</a>
                        <a href="{{ route('guilds.themes.index', $guild) }}" class="text-neutral-400 hover:text-neutral-100">Collection themes</a>
                        <a href="{{ route('guilds.event-role-sets.index', $guild) }}" class="text-neutral-400 hover:text-neutral-100">Event role sets</a>
                        <a href="{{ route('guilds.events.index', $guild) }}" class="text-neutral-400 hover:text-neutral-100">Events</a>
                        <a href="{{ route('guilds.giveaways.create', $guild) }}" class="text-neutral-400 hover:text-neutral-100">Giveaways</a>
                        <a href="{{ route('guilds.standard-giveaways.index', $guild) }}" class="text-neutral-400 hover:text-neutral-100">Standard giveaways</a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
