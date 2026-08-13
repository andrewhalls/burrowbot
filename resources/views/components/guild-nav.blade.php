@props(['guild', 'active'])

@php
    $links = [
        'settings' => ['label' => 'Settings', 'route' => 'guilds.settings'],
        'themes' => ['label' => 'Collection themes', 'route' => 'guilds.themes.index'],
        'event-role-sets' => ['label' => 'Event role sets', 'route' => 'guilds.event-role-sets.index'],
        'events' => ['label' => 'Events', 'route' => 'guilds.events.index'],
        'giveaways' => ['label' => 'Giveaways', 'route' => 'guilds.giveaways.create'],
        'standard-giveaways' => ['label' => 'Standard giveaways', 'route' => 'guilds.standard-giveaways.index'],
    ];
@endphp

<nav class="flex flex-wrap gap-x-4 gap-y-1 text-sm border-b border-neutral-800 pb-4 mb-6">
    <a href="{{ route('dashboard') }}" class="text-neutral-400 hover:text-neutral-100">&larr; Dashboard</a>
    @foreach ($links as $key => $link)
        <a href="{{ route($link['route'], $guild) }}"
           class="{{ $key === $active ? 'text-indigo-400 font-medium' : 'text-neutral-400 hover:text-neutral-100' }}">
            {{ $link['label'] }}
        </a>
    @endforeach
</nav>
