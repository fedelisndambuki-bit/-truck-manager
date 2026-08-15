<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Tanaki Hub</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/tanaki-hub-icon-light.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter', 'sans-serif'] } } } };
    </script>
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

    <main class="max-w-3xl mx-auto px-6 py-24">
        <h1 class="text-4xl font-black text-slate-900 mb-4 text-center">Get in Touch</h1>
        <p class="text-lg text-slate-500 max-w-xl mx-auto text-center mb-16 leading-relaxed">
            Have a question about Tanaki Hub? We'd love to hear from you.
        </p>

        <div class="grid sm:grid-cols-2 gap-6">
            <div class="bg-slate-50 rounded-2xl p-8 text-center">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-4" style="background-color: #2F4F4F;">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div class="font-bold text-slate-900 mb-1">Email</div>
                <a href="mailto:{{ \App\Models\PlatformSetting::getSetting('contact_email', 'support@tanakihub.com') }}" class="text-sm font-semibold" style="color: #12b5a0;">
                    {{ \App\Models\PlatformSetting::getSetting('contact_email', 'support@tanakihub.com') }}
                </a>
            </div>
            <div class="bg-slate-50 rounded-2xl p-8 text-center">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-4" style="background-color: #2F4F4F;">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </div>
                <div class="font-bold text-slate-900 mb-1">Phone</div>
                @php($phone = \App\Models\PlatformSetting::getSetting('contact_phone'))
                @if($phone)
                    <a href="tel:{{ $phone }}" class="text-sm font-semibold" style="color: #12b5a0;">{{ $phone }}</a>
                @else
                    <span class="text-sm text-slate-400">Available via email</span>
                @endif
            </div>
        </div>
    </main>

    <footer class="py-10 text-center text-sm text-slate-400 border-t border-slate-100">
        &copy; {{ now()->year }} Tanaki Technologies Ltd. All rights reserved.
    </footer>
</body>
</html>
