<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    {{-- Without this, a browser in dark mode applies its own dark-mode
         defaults to native form controls (select text/background, etc.) on
         these plain standalone pages — Filament panel pages don't have this
         problem since Filament's own layout already declares it. --}}
    <meta name="color-scheme" content="light" />
    <title>{{ $title ?? 'Tanaki Hub' }} — Tanaki Hub</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/tanaki-hub-icon-light.png') }}">
    @vite(['resources/css/auth.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased">
    {{ $slot }}
    @livewireScripts
</body>
</html>
