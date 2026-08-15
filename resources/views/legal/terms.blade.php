<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Service - Tanaki Hub</title>
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
        <h1 class="text-4xl font-black text-slate-900 mb-2">Terms of Service</h1>
        <p class="text-slate-500 mb-12">Last updated: {{ now()->format('F Y') }}</p>

        <div class="space-y-10 leading-relaxed">
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">1. Acceptance of Terms</h2>
                <p>By accessing or using Tanaki Hub, you agree to be bound by these Terms of Service. If you do not agree to these terms, do not use the platform.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">2. Use of the Platform</h2>
                <p>You may use Tanaki Hub only for lawful business purposes related to fleet, construction, quarry, and related industrial operations management, in accordance with your subscription plan.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">3. Account Responsibilities</h2>
                <p>You are responsible for maintaining the confidentiality of your account credentials and for all activity that occurs under your account.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">4. Subscription & Billing</h2>
                <p>Paid plans are billed in advance on a recurring basis. Fees are non-refundable except where required by law.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">5. Termination</h2>
                <p>We may suspend or terminate your access to the platform if you violate these terms or fail to pay applicable fees.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">6. Limitation of Liability</h2>
                <p>Tanaki Hub is provided "as is" without warranties of any kind. To the maximum extent permitted by law, we are not liable for indirect, incidental, or consequential damages arising from your use of the platform.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">7. Contact Us</h2>
                <p>Questions about these Terms can be sent to
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
