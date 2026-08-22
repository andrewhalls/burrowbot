@props(['guild' => null, 'active' => null])

@php
    $routeNameToActiveKey = [
        'guilds.settings' => 'settings',
        'guilds.themes.index' => 'themes',
        'guilds.event-role-sets.index' => 'event-role-sets',
        'guilds.events.index' => 'events',
        'guilds.giveaways.index' => 'giveaways',
        'guilds.giveaways.create' => 'giveaways',
        'guilds.giveaways.show' => 'giveaways',
        'guilds.standard-giveaways.index' => 'standard-giveaways',
        'guilds.broadcasts.index' => 'broadcasts',
        'guilds.admins.index' => 'admins',
    ];
    $active ??= $routeNameToActiveKey[request()->route()?->getName()] ?? null;

    // 'admins' is deliberately not one of the seven grantable dashboard
    // sections (App\Support\GuildAdmins\GuildAdminSection) - it's gated
    // separately below, on the manageAdmins ability, so a scoped admin can
    // never reach it regardless of which sections they hold.
    $allLinks = [
        'settings' => ['label' => 'Settings', 'route' => 'guilds.settings'],
        'themes' => ['label' => 'Collection themes', 'route' => 'guilds.themes.index'],
        'event-role-sets' => ['label' => 'Event role sets', 'route' => 'guilds.event-role-sets.index'],
        'events' => ['label' => 'Events', 'route' => 'guilds.events.index'],
        'giveaways' => ['label' => 'Popup giveaways', 'route' => 'guilds.giveaways.index'],
        'standard-giveaways' => ['label' => 'Standard giveaways', 'route' => 'guilds.standard-giveaways.index'],
        'broadcasts' => ['label' => 'Broadcasts', 'route' => 'guilds.broadcasts.index'],
        'admins' => ['label' => 'Admins', 'route' => 'guilds.admins.index'],
    ];

    $icons = [
        'settings' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z|M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'themes' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z|M6 6h.008v.008H6V6z',
        'event-role-sets' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.106A12.318 12.318 0 008.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0112.749 0zM15 19.128v.106A9.38 9.38 0 018.625 21a9.337 9.337 0 01-4.121-.952 4.125 4.125 0 017.533-2.493M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
        'events' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        'giveaways' => 'M12 8.25v13.5M12 8.25c-1.03-1.03-2.5-1.66-4.108-1.66-1.716 0-3.107 1.05-3.107 2.35 0 1.3 1.39 2.35 3.107 2.35H12M12 8.25c1.03-1.03 2.5-1.66 4.108-1.66 1.716 0 3.107 1.05 3.107 2.35 0 1.3-1.39 2.35-3.107 2.35H12M4.5 11.29h15v4.71a2.25 2.25 0 01-2.25 2.25h-10.5a2.25 2.25 0 01-2.25-2.25v-4.71z',
        'standard-giveaways' => 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z',
        'broadcasts' => 'M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73s-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46',
        'admins' => 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z',
    ];

    $links = $guild
        ? collect($allLinks)->filter(fn ($link, $key) => $key === 'admins'
            ? auth()->user()->can('manageAdmins', $guild)
            : auth()->user()->hasGuildAdminSection($guild, $key))
        : collect();
@endphp

<aside class="w-20 shrink-0 border-r border-line bg-surface flex flex-col items-center gap-6 py-5">
    <a href="{{ route('dashboard') }}" title="Dashboard"
       class="flex h-10 w-10 items-center justify-center rounded-control bg-accent text-accent-ink text-sm font-semibold">
        B
        <span class="sr-only">Dashboard</span>
    </a>

    @if ($guild)
        <nav class="flex flex-1 flex-col items-center gap-2">
            @foreach ($links as $key => $link)
                <a href="{{ route($link['route'], $guild) }}" title="{{ $link['label'] }}"
                   class="flex h-11 w-11 items-center justify-center rounded-control transition-colors {{ $key === $active ? 'bg-accent text-accent-ink' : 'text-muted hover:bg-surface-hover hover:text-ink' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        @foreach (explode('|', $icons[$key]) as $path)
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
                        @endforeach
                    </svg>
                    <span class="sr-only">{{ $link['label'] }}</span>
                </a>
            @endforeach
        </nav>
    @else
        <div class="flex-1"></div>
    @endif
</aside>
