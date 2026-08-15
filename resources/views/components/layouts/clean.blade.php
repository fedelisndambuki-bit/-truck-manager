@props([
    'sidebar' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    @stack('styles')
</head>
<body class="h-full overflow-hidden antialiased">
    <div class="flex h-screen overflow-hidden bg-gray-100">
        <!-- Sidebar -->
        <aside class="w-64 shrink-0 flex flex-col bg-teal-900 text-white transition-all duration-300">
            <div class="flex items-center h-16 px-6 bg-teal-950/50">
                <span class="text-xl font-bold tracking-tight">TruckManager</span>
            </div>
            <nav class="flex-1 overflow-y-auto p-4 space-y-2">
                {{ $sidebar }}
            </nav>
            <div class="p-4 border-t border-teal-800">
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="w-8 h-8 rounded-full bg-teal-700 flex items-center justify-center text-xs font-medium">
                        {{ substr(auth()->user()?->name ?? 'U', 0, 1) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">{{ auth()->user()?->name }}</p>
                        <p class="text-xs text-teal-400 truncate">{{ auth()->user()?->email }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Topbar (Optional) -->
            <header class="h-16 shrink-0 flex items-center justify-between px-8 bg-white border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">{{ $header ?? '' }}</h2>
                <div class="flex items-center gap-4">
                    <!-- Global Search or Actions -->
                    <button onclick="window.location.href='/admin'" class="text-sm font-medium text-teal-700 hover:text-teal-800">
                        Admin Dashboard <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </button>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                {{ $slot }}
            </div>
        </main>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
    @stack('scripts')
</body>
</html>
