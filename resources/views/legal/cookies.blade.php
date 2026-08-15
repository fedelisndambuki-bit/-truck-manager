<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookie Policy - Tanaki Hub</title>
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

    <main class="max-w-4xl mx-auto px-6 py-16">
        <h1 class="text-4xl font-black text-slate-900 mb-2">Cookie Policy</h1>
        <p class="text-slate-500 mb-12">Last updated: {{ now()->format('F Y') }}</p>

        <div class="space-y-10 leading-relaxed">
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">1. What Are Cookies</h2>
                <p>Cookies are small text files stored on your device when you visit our website. They help the site function correctly and let us understand how it's being used.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">2. Cookies We Use</h2>
                <p>We use essential cookies required for login sessions and core site functionality, and, where enabled, analytics cookies that help us understand aggregate usage of the platform. We do not use cookies to sell your personal information.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">3. Managing Cookies</h2>
                <p>Most browsers let you control cookies through their settings, including blocking or deleting them. Disabling essential cookies may prevent parts of Tanaki Hub, such as staying signed in, from working correctly.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">4. Changes to This Policy</h2>
                <p>We may update this Cookie Policy from time to time. Changes will be posted on this page with an updated revision date.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">5. Contact Us</h2>
                <p>Questions about this Cookie Policy can be sent to
                    <a href="mailto:{{ \App\Models\PlatformSetting::getSetting('contact_email', 'support@tanakihub.com') }}" class="font-semibold" style="color: #12b5a0;">{{ \App\Models\PlatformSetting::getSetting('contact_email', 'support@tanakihub.com') }}</a>.
                </p>
            </section>
        </div>
    </main>

    <footer class="py-10 text-center text-sm text-slate-400 border-t border-slate-100">
        &copy; {{ now()->year }} Tanaki Technologies Ltd. All rights reserved.
    </footer>
</body>
</html>
