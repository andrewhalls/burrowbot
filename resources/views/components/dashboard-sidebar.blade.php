@props(['guild' => null, 'active' => null])

@php
    $routeNameToActiveKey = [
        'guilds.settings' => 'settings',
        'guilds.themes.index' => 'themes',
        'guilds.event-role-sets.index' => 'event-role-sets',
        'guilds.events.index' => 'events',
        'guilds.giveaways.create' => 'giveaways',
        'guilds.giveaways.show' => 'giveaways',
        'guilds.standard-giveaways.index' => 'standard-giveaways',
    ];
    $active ??= $routeNameToActiveKey[request()->route()?->getName()] ?? null;

    $links = [
        'settings' => ['label' => 'Settings', 'route' => 'guilds.settings'],
        'themes' => ['label' => 'Collection themes', 'route' => 'guilds.themes.index'],
        'event-role-sets' => ['label' => 'Event role sets', 'route' => 'guilds.event-role-sets.index'],
        'events' => ['label' => 'Events', 'route' => 'guilds.events.index'],
        'giveaways' => ['label' => 'Giveaways', 'route' => 'guilds.giveaways.create'],
        'standard-giveaways' => ['label' => 'Standard giveaways', 'route' => 'guilds.standard-giveaways.index'],
    ];

    $icons = [
        'settings' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z|M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'themes' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z|M6 6h.008v.008H6V6z',
        'event-role-sets' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.106A12.318 12.318 0 008.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0112.749 0zM15 19.128v.106A9.38 9.38 0 018.625 21a9.337 9.337 0 01-4.121-.952 4.125 4.125 0 017.533-2.493M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z',
        'events' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        'giveaways' => 'M12 8.25v13.5M12 8.25c-1.03-1.03-2.5-1.66-4.108-1.66-1.716 0-3.107 1.05-3.107 2.35 0 1.3 1.39 2.35 3.107 2.35H12M12 8.25c1.03-1.03 2.5-1.66 4.108-1.66 1.716 0 3.107 1.05 3.107 2.35 0 1.3-1.39 2.35-3.107 2.35H12M4.5 11.29h15v4.71a2.25 2.25 0 01-2.25 2.25h-10.5a2.25 2.25 0 01-2.25-2.25v-4.71z',
        'standard-giveaways' => 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.562.562 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z',
    ];
@endphp

<aside class="w-64 shrink-0 border-r border-line bg-surface flex flex-col">
    <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-6 py-5 text-lg font-semibold text-ink">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-control bg-accent text-accent-ink text-sm">B</span>
        Burrow
    </a>

    @if ($guild)
        <p class="px-6 pb-2 text-xs font-medium uppercase tracking-wide text-muted">{{ $guild->name }}</p>

        <nav class="flex-1 space-y-1 px-3">
            @foreach ($links as $key => $link)
                <a href="{{ route($link['route'], $guild) }}"
                   class="flex items-center gap-3 rounded-control px-3 py-2 text-sm transition-colors {{ $key === $active ? 'bg-accent text-accent-ink font-medium' : 'text-muted hover:bg-surface-hover hover:text-ink' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 shrink-0">
                        @foreach (explode('|', $icons[$key]) as $path)
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}" />
                        @endforeach
                    </svg>
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>
    @else
        <nav class="flex-1 px-3">
            <p class="px-3 py-2 text-sm text-muted">Select a guild to get started.</p>
        </nav>
    @endif
</aside>
