<x-layout title="Sign in">
    <div class="m-auto max-w-sm w-full px-6 py-12 text-center">
        <h1 class="text-2xl font-semibold mb-2">Burrow</h1>
        <p class="text-muted mb-8">Sign in with the Discord account you administer your server with.</p>

        @if (session('error'))
            <p class="mb-4 text-sm text-danger">{{ session('error') }}</p>
        @endif

        <a href="{{ route('auth.discord.redirect') }}"
           class="inline-flex items-center justify-center gap-2 w-full rounded-card bg-accent px-4 py-2.5 font-medium hover:bg-accent-hover transition">
            Sign in with Discord
        </a>
    </div>
</x-layout>
