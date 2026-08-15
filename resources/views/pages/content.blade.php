<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->title }} - Tanaki Hub</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/tanaki-hub-icon-light.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } } };
    </script>
    <style>
        .content-body h2 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-top: 2.5rem; margin-bottom: 0.75rem; }
        .content-body h2:first-child { margin-top: 0; }
        .content-body p { margin-bottom: 1rem; line-height: 1.75; }
        .content-body ul { list-style: disc; padding-left: 1.5rem; margin-bottom: 1rem; }
        .content-body a { color: #12b5a0; font-weight: 600; }
    </style>
</head>
<body class="antialiased text-slate-700 font-sans">
    <nav class="sticky top-0 z-50 border-b border-slate-100" style="background-color: #2F4F4F;">
        <div class="max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/brand/tanaki-hub-icon-dark.png') }}" alt="Tanaki Hub" class="h-9 w-9 object-contain">
                <span class="text-xl font-black text-white tracking-tight">Tanaki Hub</span>
            </a>
            <a href="{{ url('/') }}" class="text-white/80 hover:text-white text-sm font-medium">&larr; Back to home</a>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-6 py-16">
        <h1 class="text-4xl font-black text-slate-900 mb-2">{{ $page->title }}</h1>
        <p class="text-slate-500 mb-12">Last updated: {{ $page->updated_at->format('F Y') }}</p>

        <div class="content-body text-slate-700">
            {!! $page->content !!}
        </div>
    </main>

    <footer class="py-10 text-center text-sm text-slate-400 border-t border-slate-100">
        &copy; {{ now()->year }} Tanaki Technologies Ltd. All rights reserved.
    </footer>
</body>
</html>
