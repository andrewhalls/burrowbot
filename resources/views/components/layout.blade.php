<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <script>
        // Blocking, dependency-free, and placed before any CSS paints so
        // there is never a flash of the wrong theme (design.md Decision 2).
        // Dark is the unconditioned default - this only ever adds the
        // light-mode override attribute, it never needs to add a dark one.
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
        }
        window.burrowToggleTheme = function () {
            var isLight = document.documentElement.getAttribute('data-theme') === 'light';
            if (isLight) {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light');
            }
        };
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-canvas text-ink antialiased">
    @auth
        @php
            // request()->route('guild') is only ever a resolved Guild
            // instance when the current page's own mount() type-hints
            // `Guild $guild` itself; pages that instead bind a more
            // specific child model (EventOccurrence, Giveaway, ...) leave
            // the {guild} URI segment as Laravel never had reason to
            // resolve it, so it comes back as the raw route parameter.
            $routeGuildParam = request()->route('guild');
            $currentGuild = $routeGuildParam instanceof \App\Models\Guild
                ? $routeGuildParam
                : ($routeGuildParam ? \App\Models\Guild::find($routeGuildParam) : null);
            $administeredGuilds = auth()->user()->guildAdmins()->with('guild')->get()->pluck('guild');
        @endphp
        <div class="min-h-screen flex">
            <x-dashboard-sidebar :guild="$currentGuild" />

            <div class="flex-1 flex flex-col min-w-0">
                <x-dashboard-topbar :guild="$currentGuild" :administered-guilds="$administeredGuilds" />

                <main class="flex-1 overflow-y-auto p-6">
                    {{ $slot }}
                </main>
            </div>
        </div>
    @else
        <div class="min-h-screen flex flex-col">
            {{ $slot }}
        </div>
    @endauth
    @livewireScripts
</body>
</html>
