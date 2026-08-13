<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-neutral-950 text-neutral-100 antialiased">
    <div class="min-h-screen flex flex-col">
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>
