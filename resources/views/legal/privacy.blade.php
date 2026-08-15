<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - Tanaki Hub</title>
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
        <h1 class="text-4xl font-black text-slate-900 mb-2">Privacy Policy</h1>
        <p class="text-slate-500 mb-12">Last updated: {{ now()->format('F Y') }}</p>

        <div class="space-y-10 leading-relaxed">
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">1. Information We Collect</h2>
                <p>We collect information you provide directly to us when you create an account, use our fleet management platform, or contact our support team — including your name, email address, phone number, company details, and operational data you input into the platform (vehicles, drivers, trips, and related records).</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">2. How We Use Your Information</h2>
                <p>We use the information we collect to provide, maintain, and improve Tanaki Hub, to process transactions, to send you technical notices and support messages, and to comply with legal obligations.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">3. Data Sharing</h2>
                <p>We do not sell your personal information. We may share data with service providers who perform services on our behalf (such as hosting and payment processing), or when required by law.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">4. Data Security</h2>
                <p>We implement reasonable technical and organizational measures designed to protect the security of your information, including encryption in transit and at rest.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">5. Your Rights</h2>
                <p>You may request access to, correction of, or deletion of your personal data at any time by contacting us at the address below.</p>
            </section>
            <section>
                <h2 class="text-xl font-bold text-slate-900 mb-3">6. Contact Us</h2>
                <p>If you have questions about this Privacy Policy, contact us at
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
