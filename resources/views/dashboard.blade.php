<x-layout title="Dashboard">
    <div class="max-w-5xl mx-auto w-full px-6 py-10">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-xl font-semibold">Burrow</h1>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-neutral-400 hover:text-neutral-100">Sign out</button>
            </form>
        </div>

        <p class="text-neutral-400">Signed in as {{ auth()->user()->name }}.</p>
    </div>
</x-layout>
