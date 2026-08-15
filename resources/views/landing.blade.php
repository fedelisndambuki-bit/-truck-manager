<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $seo?->meta_title ?? 'Tanaki Hub - Smart Fleet Management' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/brand/tanaki-hub-icon-light.png') }}">
    <meta name="description" content="{{ $seo?->meta_description ?? '' }}">
    <meta name="keywords" content="{{ $seo?->meta_keywords ?? '' }}">
    @if($seo?->og_image)
    <meta property="og:image" content="{{ asset('storage/' . $seo->og_image) }}">
    @endif
    @if($seo?->google_analytics_id)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $seo->google_analytics_id }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $seo->google_analytics_id }}');
    </script>
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    animation: {
                        'blob1': 'blob1 20s ease-in-out infinite',
                        'blob2': 'blob2 25s ease-in-out infinite',
                        'blob3': 'blob3 15s ease-in-out infinite',
                        'blob4': 'blob4 18s ease-in-out infinite',
                        'float-up': 'floatUp 3s ease-in-out infinite',
                        'float-down': 'floatDown 4s ease-in-out infinite',
                        'spin-slow': 'spin 50s linear infinite',
                        'fade-badge': 'fadeBadge 2s ease-in-out infinite',
                    },
                    keyframes: {
                        blob1: { '0%,100%': { transform: 'scale(1) rotate(0deg)' }, '50%': { transform: 'scale(1.2) rotate(90deg)' } },
                        blob2: { '0%,100%': { transform: 'scale(1) rotate(0deg)' }, '50%': { transform: 'scale(1.3) rotate(-90deg)' } },
                        blob3: { '0%,100%': { transform: 'scale(1)', opacity: '.3' }, '50%': { transform: 'scale(1.5)', opacity: '.5' } },
                        blob4: { '0%,100%': { transform: 'scale(1)', opacity: '.3' }, '50%': { transform: 'scale(1.5)', opacity: '.5' } },
                        floatUp: { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-10px)' } },
                        floatDown: { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(10px)' } },
                        fadeBadge: { '0%,100%': { boxShadow: '0 0 0 0 rgba(34,197,94,.45)' }, '50%': { boxShadow: '0 0 0 8px rgba(34,197,94,0)' } }
                    }
                }
            }
        }
    </script>
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(to bottom, #F8FAFC, #FFFFFF, #EFF6FF); }

        .nav-glass { background: #1e293b; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
        .glass { background: rgba(255,255,255,.08); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,.18); }

        .reveal { opacity: 0; transform: translateY(28px); transition: opacity .65s ease, transform .65s ease; }
        .reveal.visible { opacity: 1; transform: none; }

        .hover-lift { transition: transform .25s ease, box-shadow .25s ease; }
        .hover-lift:hover { transform: translateY(-8px) scale(1.02); }

        .operation-pill { transition: background-color .2s ease, border-color .2s ease, color .2s ease; }
        .operation-pill-selected { background-color: #2F4F4F !important; border-color: #2F4F4F !important; color: #fff !important; }
        .module-category-card { transition: border-color .2s ease, box-shadow .2s ease; }
        .module-category-highlighted { border-color: #12b5a0 !important; box-shadow: 0 10px 25px -12px rgba(18, 181, 160, 0.4); }

        .pricing-card { transition: transform .3s ease, box-shadow .3s ease; }
        .pricing-card:hover { transform: translateY(-12px) scale(1.02); }

        .phone-notch { position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 8rem; height: 1.75rem; background: #111827; border-radius: 0 0 1.5rem 1.5rem; z-index: 10; }
        #mobile-menu { display: none; }
        #mobile-menu.open { display: block; }

        @keyframes heroFadeUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: none; } }
        .hero-anim-1 { animation: heroFadeUp .6s 0s ease both; }
        .hero-anim-2 { animation: heroFadeUp .6s .12s ease both; }
        .hero-anim-3 { animation: heroFadeUp .6s .24s ease both; }
        .hero-anim-4 { animation: heroFadeUp .6s .36s ease both; }
    </style>
</head>
<body class="antialiased text-slate-900">

    @if(session('trial_expired'))
    <div id="trial-expired-banner" style="background:#dc2626;color:#fff;text-align:center;padding:14px 20px;font-size:15px;font-weight:600;position:relative;z-index:100;">
        ⚠️ Your 7-day free trial has expired. Please
        <a href="{{ route('register') }}" style="color:#fff;text-decoration:underline;font-weight:800;">create a new account</a>
        or <a href="mailto:{{ \App\Models\PlatformSetting::getSetting('contact_email', 'support@tanakihub.com') }}" style="color:#fff;text-decoration:underline;">contact support</a> to continue.
        <button onclick="document.getElementById('trial-expired-banner').remove()" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);background:none;border:none;color:#fff;font-size:20px;cursor:pointer;">×</button>
    </div>
    @endif

    <!-- NAVIGATION -->
    <nav id="navbar" class="nav-glass sticky top-0 z-50 border-b border-gray-200/10 transition-shadow">
        <div class="max-w-[100%] mx-auto px-6 py-4 flex items-center justify-between">
            <!-- Logo -->
            <div class="flex items-center gap-3 cursor-pointer mr-auto" onclick="window.scrollTo({top:0, behavior:'smooth'})">
                <img src="{{ asset('images/brand/tanaki-hub-icon-dark.png') }}" alt="Tanaki Hub" class="h-9 w-9 object-contain">
                <span class="text-2xl font-black text-white tracking-tight">Tanaki Hub</span>
            </div>

            <!-- Desktop Nav + Auth -->
            <div class="hidden md:flex items-center gap-8">
                <div class="flex items-center gap-8">
                    @foreach($navigations as $nav)
                    <a href="{{ $nav->link }}" class="text-white/80 hover:text-white transition-colors font-medium text-sm">{{ $nav->title }}</a>
                    @endforeach
                </div>

                <div class="flex items-center gap-6">
                    <a href="{{ route('login') }}" class="text-white/80 hover:text-white transition-colors font-medium text-sm">Login</a>
                    <a href="{{ route('register') }}" class="text-white px-6 py-2.5 rounded-full font-bold text-sm hover:shadow-xl hover:scale-105 transition-all" style="background-color: #2F4F4F;">Get Started Free</a>
                </div>
            </div>

            <!-- Mobile Toggle -->
            <button onclick="toggleMenu()" class="md:hidden text-white p-2">
                <svg id="icon-open" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                <svg id="icon-close" class="w-7 h-7 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="md:hidden bg-[#1e293b] border-t border-white/10 px-6 py-8">
            <div class="flex flex-col gap-6">
                @foreach($navigations as $nav)
                <a href="{{ $nav->link }}" onclick="toggleMenu()" class="text-white/80 text-lg font-medium text-left">{{ $nav->title }}</a>
                @endforeach
                <div class="h-px bg-white/10 my-2"></div>
                <a href="{{ route('login') }}" class="text-white/80 text-lg font-medium">Login</a>
                <a href="{{ route('register') }}" class="text-white py-4 rounded-2xl text-center font-bold text-lg shadow-lg" style="background-color: #2F4F4F;">Get Started Free</a>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="py-20 md:py-32 relative overflow-hidden" style="background: {{ $hero?->background_style ?? '#2F4F4F' }};">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-white/5 blur-[120px] rounded-full animate-blob1"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-white/5 blur-[120px] rounded-full animate-blob2"></div>

        <div class="max-w-7xl mx-auto px-6 text-center">
            <div class="hero-anim-1 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 border border-white/20 mb-8">
                <svg class="w-4 h-4 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1a1 1 0 10-2 0v1a1 1 0 102 0zM14.243 14.243a1 1 0 10-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM16 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1z"/></svg>
                <span class="text-sm font-bold text-white tracking-wide uppercase">{{ $hero?->badge_text ?? 'Launching the Future of Fleet Management' }}</span>
            </div>

            <h1 class="hero-anim-2 text-5xl md:text-8xl font-black mb-8 leading-[1.1] tracking-tight">
                @if($hero?->headline)
                    @php
                        $parts = explode(',', $hero->headline, 2);
                    @endphp
                    <span class="text-white">{{ trim($parts[0]) }}</span>
                    @if(isset($parts[1]))
                        <br><span class="text-white">{{ trim($parts[1]) }}</span>
                    @endif
                @else
                    <span class="text-white">Smart Fleet,</span><br>
                    <span class="text-white">Smarter Business</span>
                @endif
            </h1>

            <p class="hero-anim-3 max-w-2xl mx-auto text-xl md:text-2xl text-slate-300 mb-12 leading-relaxed">
                {{ $hero?->subheadline ?? 'Transform your transport operations with AI-powered fleet intelligence. Manage everything from trucks to drivers, all in one beautiful platform.' }}
            </p>

            <div class="hero-anim-4 flex flex-col md:flex-row items-center justify-center gap-4 mb-24">
                <a href="{{ $hero?->primary_button_link ?? route('register') }}" class="group text-white px-10 py-5 rounded-2xl font-bold text-lg flex items-center gap-2 hover:shadow-2xl hover:-translate-y-1 transition-all" style="background-color: #2F4F4F; border: 1px solid rgba(255,255,255,0.2);">
                    {{ $hero?->primary_button_text ?? 'Start Free Trial' }}
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <a href="{{ $hero?->secondary_button_link ?? '#mobile-app' }}" class="w-full md:w-auto px-10 py-5 rounded-2xl font-bold text-lg border-2 border-white text-white hover:bg-white hover:text-[#2F4F4F] transition-all">
                    {{ $hero?->secondary_button_text ?? 'See Mobile App' }}
                </a>
            </div>

            <!-- Stats Grid -->
            @php
                $statGradients = ['from-blue-500/10 to-cyan-500/10', 'from-purple-500/10 to-pink-500/10', 'from-green-500/10 to-emerald-500/10'];
                $statIcons = [
                    'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
                    'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                    'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
                ];
                $fallbackStats = collect([
                    (object) ['value' => '10k+', 'label' => 'Active Fleets'],
                    (object) ['value' => '50k+', 'label' => 'Happy Drivers'],
                    (object) ['value' => '25+', 'label' => 'Countries'],
                ]);
                $statsToShow = $stats->count() > 0 ? $stats : $fallbackStats;
            @endphp
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                @foreach($statsToShow as $i => $stat)
                <div class="group bg-white rounded-[2.5rem] p-8 hover-lift relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br {{ $statGradients[$i % 3] }} opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-white mb-6 mx-auto shadow-lg" style="background-color: #2F4F4F;">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $statIcons[$i % 3] }}"/></svg>
                    </div>
                    <div class="text-4xl font-black text-slate-900 mb-1">{{ $stat->value }}</div>
                    <div class="text-slate-500 font-medium">{{ $stat->label }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- FEATURES GRID SECTION -->
    <section id="features" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-20 reveal">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-50 border border-blue-100 mb-4">
                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1a1 1 0 10-2 0v1a1 1 0 102 0zM14.243 14.243a1 1 0 10-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM16 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1z"/></svg>
                    <span class="text-sm font-bold text-blue-700 uppercase tracking-widest">{{ $featuresSection?->badge_text ?? 'Powerful Features' }}</span>
                </div>
                <h2 class="text-4xl md:text-6xl font-black text-slate-900 mb-6">
                    @php
                        $featuresHeadingParts = explode(',', $featuresSection?->heading ?? "Everything You Need,Nothing You Don't", 2);
                    @endphp
                    {{ trim($featuresHeadingParts[0]) }}<br>
                    @if(isset($featuresHeadingParts[1]))
                        <span class="text-black">{{ trim($featuresHeadingParts[1]) }}</span>
                    @endif
                </h2>
            </div>

            <div id="features-grid" class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($features as $i => $f)
                <div class="hover-lift group bg-gradient-to-br from-white to-gray-50 rounded-[2.5rem] p-8 shadow-xl shadow-slate-200/50 border border-slate-100 reveal" style="transition-delay: {{ $i * 80 }}ms">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white mb-8 shadow-lg group-hover:scale-110 transition-transform" style="background: {{ $f->bg_color ?? '#2F4F4F' }} !important">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $f->icon }}"/></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-4">{{ $f->title }}</h3>
                    <p class="text-slate-500 font-medium leading-relaxed">{{ $f->description }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- MOBILE APP SECTION -->
    <section id="mobile-app" class="py-24 bg-gradient-to-br from-[#0F172A] via-[#1E293B] to-[#0F172A] relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/20 blur-[120px] rounded-full animate-blob3"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-green-500/20 blur-[120px] rounded-full animate-blob4"></div>

        <div class="max-w-7xl mx-auto px-6 relative z-10">
            <div class="grid lg:grid-cols-2 gap-20 items-center">
                <div class="reveal">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass border border-white/10 mb-6">
                        <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span class="text-sm font-bold text-green-400 uppercase tracking-widest">{{ $mobileApp?->badge_text ?? 'Mobile App for Drivers' }}</span>
                    </div>
                    <h2 class="text-4xl md:text-6xl font-black text-white mb-8">
                        @php
                            $mobileHeadingParts = explode(',', $mobileApp?->heading ?? 'Introducing,Shift Hub Driver', 2);
                        @endphp
                        {{ trim($mobileHeadingParts[0]) }}<br>
                        @if(isset($mobileHeadingParts[1]))
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-400">{{ trim($mobileHeadingParts[1]) }}</span>
                        @endif
                    </h2>
                    <p class="text-xl text-slate-300 mb-10 leading-relaxed">
                        {{ $mobileApp?->description ?? 'Empower your drivers with a powerful mobile app designed to streamline communication, automate logs, and maximize efficiency on the road.' }}
                    </p>

                    <div class="flex flex-wrap gap-4">
                        @php
                            $playStoreLink = $mobileApp?->play_store_link;
                            $appStoreLink = $mobileApp?->app_store_link;
                        @endphp
                        <!-- Google Play Badge — inert with a "Coming Soon" tag
                             until a real store link is set, instead of a dead
                             "#" link that looks clickable but does nothing. -->
                        <div class="relative">
                            <{{ $playStoreLink ? 'a' : 'div' }}
                                @if($playStoreLink) href="{{ $playStoreLink }}" target="_blank" rel="noopener" @endif
                                class="block {{ $playStoreLink ? 'hover:scale-105 transition-transform' : 'opacity-50 cursor-not-allowed' }}"
                            >
                                <svg width="200" height="60" viewBox="0 0 200 60" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="200" height="60" rx="12" fill="black"/>
                                    <path d="M42 20L25 38V12L42 20Z" fill="url(#play-grad-1)"/>
                                    <path d="M52 30L42 36L35 30L42 24L52 30Z" fill="url(#play-grad-2)"/>
                                    <path d="M42 40L25 48V38L42 30L42 40Z" fill="url(#play-grad-3)"/>
                                    <path d="M52 30L42 40V20L52 30Z" fill="url(#play-grad-4)"/>
                                    <defs>
                                        <linearGradient id="play-grad-1" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#00C4E8"/><stop offset="100%" stop-color="#00A8FF"/></linearGradient>
                                        <linearGradient id="play-grad-2" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#32DEBB"/><stop offset="100%" stop-color="#00C4E8"/></linearGradient>
                                        <linearGradient id="play-grad-3" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#FFBC00"/><stop offset="100%" stop-color="#FF8C00"/></linearGradient>
                                        <linearGradient id="play-grad-4" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#FF5252"/><stop offset="100%" stop-color="#FF0000"/></linearGradient>
                                    </defs>
                                    <text x="65" y="25" fill="white" fill-opacity="0.75" font-size="10" font-family="Arial">GET IT ON</text>
                                    <text x="65" y="45" fill="white" font-size="18" font-weight="bold" font-family="Arial">Google Play</text>
                                </svg>
                            </{{ $playStoreLink ? 'a' : 'div' }}>
                            @unless($playStoreLink)
                            <span class="absolute -top-2 -right-2 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wide bg-white text-slate-500 border border-slate-200 shadow-sm">Coming Soon</span>
                            @endunless
                        </div>
                        <!-- App Store Badge — same inert-until-set treatment. -->
                        <div class="relative">
                            <{{ $appStoreLink ? 'a' : 'div' }}
                                @if($appStoreLink) href="{{ $appStoreLink }}" target="_blank" rel="noopener" @endif
                                class="block {{ $appStoreLink ? 'hover:scale-105 transition-transform' : 'opacity-50 cursor-not-allowed' }}"
                            >
                                <svg width="185" height="60" viewBox="0 0 185 60" xmlns="http://www.w3.org/2000/svg">
                                    <rect width="185" height="60" rx="12" fill="black"/>
                                    <path d="M38.5 28.5C38.5 24.5 41.5 22.5 41.5 22.5C39.5 20.5 37.5 19.5 35.5 19.5C33.5 19.5 31.5 20.5 30.5 20.5C29.5 20.5 27.5 19.5 25.5 19.5C22.5 19.5 18.5 22.5 18.5 28.5C18.5 34.5 22.5 43.5 25.5 43.5C27.5 43.5 28.5 42.5 30.5 42.5C32.5 42.5 33.5 43.5 35.5 43.5C38.5 43.5 42.5 34.5 42.5 34.5C42.5 34.5 38.5 32.5 38.5 28.5ZM33.5 17.5C34.5 16.5 35.5 14.5 35.5 12.5C35.5 12.5 33.5 12.5 32.5 13.5C31.5 14.5 30.5 16.5 30.5 18.5C30.5 18.5 32.5 18.5 33.5 17.5Z" fill="white"/>
                                    <text x="55" y="25" fill="white" fill-opacity="0.75" font-size="10" font-family="Arial">DOWNLOAD ON THE</text>
                                    <text x="55" y="45" fill="white" font-size="18" font-weight="bold" font-family="Arial">App Store</text>
                                </svg>
                            </{{ $appStoreLink ? 'a' : 'div' }}>
                            @unless($appStoreLink)
                            <span class="absolute -top-2 -right-2 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wide bg-white text-slate-500 border border-slate-200 shadow-sm">Coming Soon</span>
                            @endunless
                        </div>
                    </div>
                </div>

                <!-- Phone Mockup -->
                <div class="relative reveal">
                    <div class="relative w-[300px] h-[600px] mx-auto group">
                        <!-- Chassis -->
                        <div class="absolute inset-0 bg-gradient-to-b from-gray-800 to-gray-950 rounded-[3rem] p-3 shadow-2xl border border-white/10">
                            <!-- Screen -->
                            <div class="bg-white rounded-[2.5rem] overflow-hidden h-full relative">
                                <div class="phone-notch"></div>
                                <img src="{{ $mobileApp?->screenshot ? asset('storage/' . $mobileApp->screenshot) : asset('images/app screenshot.jpg') }}" alt="App Screenshot" class="w-full h-full object-cover object-top rounded-[2.5rem]">
                            </div>
                        </div>

                        <!-- Floating Badges -->
                        <div class="absolute -top-4 -right-8 bg-white p-4 rounded-2xl shadow-2xl animate-float-up z-20 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-black text-slate-900">{{ $mobileApp?->rating_badge_title ?? '4.9 Rating' }}</div>
                                <div class="text-xs text-slate-500 font-bold uppercase tracking-tighter">{{ $mobileApp?->rating_badge_subtitle ?? 'Top Driver App' }}</div>
                            </div>
                        </div>

                        <div class="absolute -bottom-6 -left-8 p-4 rounded-2xl shadow-2xl animate-float-down z-20 flex items-center gap-3 text-white" style="background-color: #2F4F4F;">
                            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.381z" clip-rule="evenodd"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-black">{{ $mobileApp?->payout_badge_title ?? 'Fast Payouts' }}</div>
                                <div class="text-xs text-white/80 font-bold uppercase tracking-tighter">{{ $mobileApp?->payout_badge_subtitle ?? 'Instant Settlements' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PRICING SECTION -->
    <section id="pricing" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-12 reveal">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-50 border border-green-100 mb-4">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.184c-.622.117-1.195.342-1.676.662C6.602 13.235 6 14.01 6 15c0 .99.602 1.765 1.324 2.246A4.535 4.535 0 009 17.908V19a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662c.722-.481 1.324-1.256 1.324-2.246 0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 13.092v-1.184c.622-.117 1.195-.342 1.676-.662C13.398 10.765 14 9.99 14 9c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 6.092V5z" clip-rule="evenodd"/></svg>
                    <span class="text-sm font-bold text-green-700 uppercase tracking-widest">{{ $pricingSection?->badge_text ?? 'Flexible Plans' }}</span>
                </div>
                <h2 class="text-4xl md:text-6xl font-black text-slate-900 mb-4">{{ $pricingSection?->heading ?? 'Plans that fit your operation' }}</h2>
                <p class="text-xl text-slate-500">{{ $pricingSection?->subtext ?? 'One platform. Choose the operations you need. Add more as you grow.' }}</p>
            </div>

            @php
                // ── Module catalog for the 3 "Build Tanaki around your
                // operation" cards. Presentational grouping only (blade-level),
                // not a change to the real `modules.category` column.
                $moduleCategoryGroups = [
                    'fleet' => [
                        'label' => 'Fleet & Transport',
                        'description' => 'Vehicles, drivers, trips, fuel and maintenance.',
                        'slugs' => ['fleet-management', 'driver-management', 'trip-tracking', 'fuel-logs', 'maintenance'],
                        'shortLabels' => ['Fleet', 'Drivers', 'Trips', 'Fuel', 'Maintenance'],
                    ],
                    'construction' => [
                        'label' => 'Construction',
                        'description' => 'Projects, equipment, materials and subcontractors.',
                        'slugs' => ['project_finance.view', 'eam.view', 'materials.view', 'subcontractors.view'],
                        'shortLabels' => ['Projects', 'Equipment', 'Materials', 'Subcontractors'],
                    ],
                    'quarry' => [
                        'label' => 'Quarry',
                        'description' => 'Production, weighbridge, stockpiles and dispatch.',
                        'slugs' => ['production.view', 'weighbridge.view', 'stockpile.view', 'dispatch.view'],
                        'shortLabels' => ['Production', 'Weighbridge', 'Stockpile', 'Dispatch'],
                    ],
                ];
                $allModulesBySlug = \App\Models\Module::all()->keyBy('slug');

                // Operation pill → which of the 3 module cards it highlights.
                $operationCategoryMap = [
                    'fleet' => ['fleet'],
                    'construction' => ['construction'],
                    'quarry' => ['quarry'],
                    'multi' => ['fleet', 'construction', 'quarry'],
                ];
            @endphp

            <!-- COMPACT OPERATION SELECTOR -->
            <div class="text-center mb-14 reveal">
                <div class="text-sm font-bold text-slate-500 mb-4">Choose your operation</div>
                <div class="inline-flex flex-wrap items-center justify-center gap-2">
                    @foreach([
                        ['key' => 'fleet', 'label' => 'Fleet & Transport'],
                        ['key' => 'construction', 'label' => 'Construction'],
                        ['key' => 'quarry', 'label' => 'Quarry'],
                        ['key' => 'multi', 'label' => 'Multi-Operation'],
                    ] as $operation)
                    <button
                        type="button"
                        data-operation-pill="{{ $operation['key'] }}"
                        aria-pressed="false"
                        onclick="toggleOperationPill(this)"
                        class="operation-pill px-4 py-2 rounded-full border-2 border-slate-200 bg-white text-sm font-semibold text-slate-600 transition-all hover:border-slate-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2"
                        style="outline-color: #12b5a0;"
                    >
                        {{ $operation['label'] }}
                    </button>
                    @endforeach
                </div>
            </div>

            @php
                // ── Plan copy — reads the real Plan record first ────────────
                // `tagline`, `access_level_label`, `cta_label`, and the
                // `features` KeyValue are all real, admin-editable columns on
                // the Plan model (Control Room → Plans). $planCopyDefaults
                // below only fills in for a plan that hasn't set these yet
                // (e.g. a brand-new plan) — it is a fallback, not the source
                // of truth. Module counts and prices are read from the real
                // Plan model too — never invented.
                $planCopyDefaults = [
                    'starter' => [
                        'tagline' => 'For businesses getting started with structured operations.',
                        'accessLevel' => 'Core operational access',
                        'features' => ['Core dashboard', 'User management', 'Basic reports', 'Notifications', 'Selected operational modules', 'Standard support'],
                        'cta' => 'Get Started',
                    ],
                    'growth' => [
                        'tagline' => 'For growing businesses that need deeper operational control.',
                        'accessLevel' => 'Expanded operational access',
                        'features' => ['Everything in Starter', 'Advanced dashboards', 'Analytics', 'Automation', 'Operational alerts', 'Roles & permissions', 'Expanded module access', 'Priority support'],
                        'cta' => 'Start Growing',
                    ],
                    'enterprise' => [
                        'tagline' => 'For organizations managing complex or multi-operation businesses.',
                        'accessLevel' => 'Full operational control',
                        'features' => ['Extensive module access', 'Advanced permissions', 'Custom workflows', 'API integrations', 'Hardware integrations', 'Advanced analytics', 'Multi-operation support', 'Dedicated support', 'Custom configuration'],
                        'cta' => 'Talk to Sales',
                    ],
                ];
            @endphp

            <!-- PRICING CARDS — the hero of the section -->
            <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto items-stretch">
                @foreach($plans as $plan)
                @php
                    $planKey = strtolower($plan->name);
                    $defaults = $planCopyDefaults[$planKey] ?? null;

                    $tagline = $plan->tagline ?? $defaults['tagline'] ?? '';
                    $accessLevel = $plan->access_level_label ?? $defaults['accessLevel'] ?? 'Operational access';
                    $ctaLabel = $plan->cta_label ?? $defaults['cta'] ?? 'Start Plan';

                    // Pricing: never invent a number. Enterprise is always
                    // "Custom" regardless of any stored value; Starter/Growth show
                    // their real configured price, or "Coming Soon" if unset.
                    if ($planKey === 'enterprise') {
                        $priceLabel = 'Custom';
                        $priceSuffix = null;
                    } elseif ($plan->price > 0) {
                        $priceLabel = 'KES ' . number_format($plan->price);
                        $priceSuffix = '/ month';
                    } else {
                        $priceLabel = 'Coming Soon';
                        $priceSuffix = null;
                    }

                    $ctaHref = $planKey === 'enterprise'
                        ? url('/contact')
                        : route('register', ['plan' => $plan->id]);

                    // Real module count from the Plan ↔ Module relationship —
                    // not a marketing number.
                    $moduleCount = $plan->modules->count();

                    // Features: the real `features` KeyValue is the source of
                    // truth. Falls back to fixed defaults, then to the plan's
                    // own module names, only if a plan has no features set at
                    // all (e.g. a brand-new plan with an empty KeyValue).
                    $featuresFromDb = collect($plan->features ?? [])
                        ->filter(fn ($value) => in_array($value, ['yes', 'true', true, '1', 1], true))
                        ->keys()
                        ->all();
                    $displayFeatures = count($featuresFromDb) > 0
                        ? $featuresFromDb
                        : ($defaults['features'] ?? $plan->modules->pluck('name')->all());
                    $maxVisibleFeatures = 6;
                    $hasMoreFeatures = count($displayFeatures) > $maxVisibleFeatures;
                @endphp
                <div class="pricing-card rounded-[2.5rem] p-10 flex flex-col {{ $plan->is_featured ? 'text-white shadow-2xl shadow-slate-900/20 border-4 border-slate-700 relative lg:-translate-y-3' : 'bg-white shadow-xl shadow-slate-200/50 border-2 border-slate-100' }} reveal" style="transition-delay: {{ $loop->index * 100 }}ms; {{ $plan->is_featured ? 'background-color: #2F4F4F;' : '' }}">
                    @if($plan->is_featured)
                    <div class="absolute -top-5 left-1/2 -translate-x-1/2 bg-white text-slate-800 px-6 py-2 rounded-full font-black text-sm uppercase tracking-widest shadow-lg animate-fade-badge flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                        Most Popular
                    </div>
                    @endif
                    <div class="mb-6">
                        <div class="text-lg font-bold {{ $plan->is_featured ? 'text-white/90' : 'text-slate-900' }} mb-2">{{ $plan->name }}</div>
                        <p class="text-sm mb-6 {{ $plan->is_featured ? 'text-white/70' : 'text-slate-500' }}">{{ $tagline }}</p>
                        <div class="flex items-baseline gap-1">
                            <span class="text-5xl font-black {{ $plan->is_featured ? 'text-white' : 'text-slate-900' }}">{{ $priceLabel }}</span>
                            @if($priceSuffix)
                            <span class="{{ $plan->is_featured ? 'text-white/80' : 'text-slate-500' }} font-medium">{{ $priceSuffix }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Access level + real module count — no invented "any N
                         modules" language; the count is $plan->modules->count(). -->
                    <div class="mb-6 px-4 py-3 rounded-2xl {{ $plan->is_featured ? 'bg-white/10' : '' }}" style="{{ !$plan->is_featured ? 'background-color: rgba(18, 181, 160, 0.08);' : '' }}">
                        <div class="font-bold text-sm {{ $plan->is_featured ? 'text-white' : 'text-slate-900' }}">{{ $accessLevel }}</div>
                        @if($moduleCount > 0)
                        <div class="text-xs mt-0.5 {{ $plan->is_featured ? 'text-white/70' : 'text-slate-500' }}">{{ $moduleCount }} {{ Str::plural('module', $moduleCount) }} included</div>
                        @endif
                    </div>

                    <ul class="space-y-4 mb-4 flex-grow">
                        @foreach(array_slice($displayFeatures, 0, $maxVisibleFeatures) as $feature)
                        <li class="flex items-center gap-3 font-medium {{ $plan->is_featured ? 'text-white' : 'text-slate-600' }}">
                            <div class="w-6 h-6 rounded-full {{ $plan->is_featured ? 'bg-white/20' : 'bg-blue-100 text-blue-600' }} flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            {{ $feature }}
                        </li>
                        @endforeach
                        @if($hasMoreFeatures)
                        <li data-extra-features="{{ $plan->id }}" class="hidden space-y-4">
                            @foreach(array_slice($displayFeatures, $maxVisibleFeatures) as $feature)
                            <div class="flex items-center gap-3 font-medium {{ $plan->is_featured ? 'text-white' : 'text-slate-600' }}">
                                <div class="w-6 h-6 rounded-full {{ $plan->is_featured ? 'bg-white/20' : 'bg-blue-100 text-blue-600' }} flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                {{ $feature }}
                            </div>
                            @endforeach
                        </li>
                        @endif
                    </ul>
                    @if($hasMoreFeatures)
                    <button
                        type="button"
                        onclick="togglePlanFeatures({{ $plan->id }}, this)"
                        class="text-sm font-bold hover:underline text-left mb-6 {{ $plan->is_featured ? 'text-white/80' : '' }}"
                        style="{{ !$plan->is_featured ? 'color: #2F4F4F;' : '' }}"
                    >
                        View all features &rarr;
                    </button>
                    @endif
                    <a href="{{ $ctaHref }}" class="{{ $plan->is_featured ? 'bg-white text-black' : 'text-white' }} py-4 rounded-2xl text-center font-bold text-lg hover:scale-105 transition-all shadow-xl" style="{{ !$plan->is_featured ? 'background-color: #2F4F4F;' : '' }}">{{ $ctaLabel }}</a>
                </div>
                @endforeach
            </div>

            <!-- BUILD TANAKI AROUND YOUR OPERATION — the one supporting section -->
            <div class="max-w-2xl mx-auto mt-20 mb-10 text-center reveal">
                <h3 class="text-2xl md:text-3xl font-black text-slate-900 mb-3">Build Tanaki around your operation</h3>
                <p class="text-slate-500">Start with what you need today. Add more capabilities as your business grows.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                @foreach($moduleCategoryGroups as $categoryKey => $category)
                @php
                    $categoryModules = collect($category['slugs'])
                        ->map(fn ($slug) => $allModulesBySlug->get($slug))
                        ->filter()
                        ->values();
                @endphp
                <div data-module-category="{{ $categoryKey }}" class="module-category-card rounded-[1rem] border-2 border-slate-100 p-8 flex flex-col reveal">
                    <div class="font-bold text-lg text-slate-900 mb-2">{{ $category['label'] }}</div>
                    <p class="text-sm text-slate-500 mb-5">{{ $category['description'] }}</p>
                    <div class="flex flex-wrap gap-2 mb-2">
                        @foreach($categoryModules as $i => $module)
                        <span class="px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-600">{{ $category['shortLabels'][$i] ?? $module->name }}</span>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- PLATFORM PREVIEW -->
    @if($previews->count() > 0)
    <section class="py-24 bg-gradient-to-br from-slate-100 to-blue-100">
        <div class="max-w-7xl mx-auto px-6 text-center">
            @if($previews->count() > 1)
            <!-- Preview switcher — only shown when there's more than one preview to choose between -->
            <div class="flex flex-wrap items-center justify-center gap-3 mb-12 reveal">
                @foreach($previews as $i => $preview)
                <button
                    type="button"
                    onclick="showPlatformPreview({{ $i }}, true)"
                    data-preview-tab="{{ $i }}"
                    class="preview-tab px-5 py-2.5 rounded-full text-sm font-bold border transition-all {{ $i === 0 ? 'text-white border-transparent' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-300' }}"
                    style="{{ $i === 0 ? 'background-color: #2F4F4F;' : '' }}"
                >
                    {{ $preview->title }}
                </button>
                @endforeach
            </div>
            @endif

            @foreach($previews as $i => $preview)
            <div data-preview-panel="{{ $i }}" class="{{ $i === 0 ? '' : 'hidden' }}">
                <h2 class="text-4xl md:text-6xl font-black mb-6 leading-tight">
                    {{ $preview->title }}
                </h2>
                <p class="text-xl text-slate-500 mb-16 max-w-2xl mx-auto">{{ $preview->description ?? 'See Tanaki Hub in action.' }}</p>

                <!-- Browser Mockup -->
                <div class="reveal relative max-w-[950px] mx-auto p-[2px] rounded-[1.5rem] bg-gradient-to-r from-blue-500 to-green-500 shadow-2xl">
                    <div class="bg-white rounded-[1.4rem] overflow-hidden">
                        <!-- Toolbar -->
                        <div class="bg-[#1e293b] px-6 py-4 flex items-center justify-between">
                            <div class="flex gap-2">
                                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                                <div class="w-3 h-3 rounded-full bg-green-400"></div>
                            </div>
                            <div class="bg-white/10 px-6 py-2 rounded-full text-xs text-white/60 font-medium tracking-wide">app.tanakihub.com/dashboard</div>
                            <div class="w-10"></div>
                        </div>
                        <!-- Content -->
                        <img src="{{ str_starts_with($preview->image, 'http') ? $preview->image : asset('storage/' . $preview->image) }}" alt="{{ $preview->title }}" class="w-full max-h-[550px] object-cover object-top">
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    <!-- TESTIMONIALS SECTION -->
    @if($testimonials->count() > 0)
    <section id="testimonials" class="py-24 bg-gray-50 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16 reveal">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-50 border border-blue-100 mb-4">
                    <span class="text-sm font-bold text-blue-700 uppercase tracking-widest">{{ $testimonialsSection?->badge_text ?? 'Testimonials' }}</span>
                </div>
                <h2 class="text-4xl md:text-6xl font-black text-slate-900 mb-4">{{ $testimonialsSection?->heading ?? 'Trusted by Industry Leaders' }}</h2>
                <p class="text-xl text-slate-500">{{ $testimonialsSection?->subtext ?? 'See what our customers have to say about their experience with Tanaki Hub.' }}</p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($testimonials as $testimonial)
                <div class="bg-white p-8 rounded-[2.5rem] shadow-xl shadow-slate-200/50 border border-slate-100 reveal">
                    <div class="flex items-center gap-1 mb-6 text-yellow-400">
                        @for($i = 0; $i < ($testimonial->rating ?? 5); $i++)
                        <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        @endfor
                    </div>
                    <p class="text-slate-600 italic mb-8 leading-relaxed">"{{ $testimonial->review }}"</p>
                    <div class="flex items-center gap-4">
                        @if($testimonial->photo)
                        <img src="{{ asset('storage/' . $testimonial->photo) }}" alt="{{ trim($testimonial->name) }}" class="w-12 h-12 rounded-full object-cover">
                        @else
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        @endif
                        <div>
                            <div class="font-black text-slate-900 text-lg">{{ $testimonial->name }}</div>
                            <div class="text-slate-500 font-bold text-sm uppercase">{{ $testimonial->company }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    <!-- CTA SECTION -->
    <section class="py-24 relative overflow-hidden" style="background: {{ $cta?->background_style ?? '#1e293b' }};">
        <div class="absolute top-0 left-0 w-[500px] h-[500px] bg-white/5 blur-[120px] rounded-full animate-spin-slow"></div>
        <div class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-white/5 blur-[120px] rounded-full animate-spin-slow" style="animation-direction: reverse;"></div>

        <div class="max-w-4xl mx-auto px-6 text-center relative z-10 reveal">
            <h2 class="text-4xl md:text-7xl font-black text-white mb-8">{{ $cta?->headline ?? 'Ready to Transform Your Fleet?' }}</h2>
            <p class="text-xl text-white/80 mb-12">{{ $cta?->subtext ?? 'Join 10,000+ companies already using Tanaki Hub to drive their business forward.' }}</p>
            <div class="flex flex-col md:flex-row items-center justify-center gap-6">
                <a href="{{ $cta?->button_link ?? route('register') }}" class="group bg-white text-blue-600 px-12 py-5 rounded-2xl font-black text-xl flex items-center gap-3 hover:scale-105 transition-all shadow-2xl">
                    {{ $cta?->button_text ?? 'Start Free Trial' }}
                    <svg class="w-6 h-6 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <button onclick="goTo('footer')" class="px-12 py-5 rounded-2xl font-bold text-xl text-white border-2 border-white/20 backdrop-blur-md hover:bg-white/10 transition-all flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    Talk to Sales
                </button>
            </div>
        </div>
    </section>

    <!-- TECHNOLOGY & INTEGRATIONS SECTION -->
    @if($partners->count() > 0)
        @php
            $partnersHasVerifiedPartner = $partners->contains(fn ($partner) => $partner->category === 'industry');
            $partnersDefaultBadge = $partnersHasVerifiedPartner ? 'Partners & Integrations' : 'Technology & Integrations';
        @endphp
        <section id="partners" class="pt-16 pb-16 md:pt-20 md:pb-20 bg-white">
            <div class="max-w-3xl mx-auto px-6 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-slate-50 border border-slate-100 mb-3 reveal">
                    <span class="text-xs font-bold uppercase tracking-widest text-[#2F4F4F]">{{ $partnersSection?->badge_text ?? $partnersDefaultBadge }}</span>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-slate-900 mb-3 reveal">
                    {{ $partnersSection?->heading ?? 'Connected to the systems your operation already uses.' }}
                </h2>
                <p class="text-slate-500 text-lg mb-8 reveal">
                    {{ $partnersSection?->subtext ?? 'Tanaki Hub brings the technology, equipment and business systems your operation relies on together in one intelligent platform.' }}
                </p>
            </div>

            <div class="max-w-5xl mx-auto px-6">
                <div class="flex flex-wrap items-center justify-center gap-x-12 gap-y-8">
                    @foreach($partners as $partner)
                        <x-partner-logo :partner="$partner" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <!-- FOOTER -->
    <footer id="footer" class="py-20 text-white" style="background-color: {{ $footer?->background_color ?? '#2F4F4F' }};">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-x-8 gap-y-12 mb-16">
                <div class="sm:col-span-2 lg:col-span-1">
                    <div class="flex items-center gap-3 mb-8">
                        <img src="{{ asset('images/brand/tanaki-hub-icon-dark.png') }}" alt="Tanaki Hub" class="h-9 w-9 object-contain">
                        <span class="text-2xl font-black text-white tracking-tight">Tanaki Hub</span>
                    </div>
                    <p class="text-slate-400 mb-8 leading-relaxed">
                        {{ $footer?->company_description ?? 'The intelligent platform for modern industrial operations.' }}
                    </p>
                    <div class="flex gap-4">
                        @if($footer?->facebook)
                        <a href="{{ $footer->facebook }}" class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center hover:bg-white/10 transition-all">
                            <svg class="w-5 h-5" fill="#1877F2" viewBox="0 0 24 24"><path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 1.972-.287 1.695h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z"/></svg>
                        </a>
                        @endif
                        @if($footer?->instagram)
                        <a href="{{ $footer->instagram }}" class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center hover:bg-white/10 transition-all">
                            <svg class="w-5 h-5" viewBox="0 0 24 24">
                                <defs>
                                    <linearGradient id="footer-ig-gradient" x1="0%" y1="100%" x2="100%" y2="0%">
                                        <stop offset="0%" stop-color="#FEDA75"/>
                                        <stop offset="35%" stop-color="#FA7E1E"/>
                                        <stop offset="65%" stop-color="#D62976"/>
                                        <stop offset="100%" stop-color="#4F5BD5"/>
                                    </linearGradient>
                                </defs>
                                <path fill="url(#footer-ig-gradient)" d="M12 0c-3.259 0-3.667.014-4.947.072-1.277.06-2.148.261-2.913.558-.789.306-1.459.717-2.126 1.384s-1.079 1.336-1.384 2.126c-.297.765-.499 1.636-.558 2.913-.058 1.28-.072 1.688-.072 4.947s.014 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.789.717 1.459 1.384 2.126s1.336 1.079 2.126 1.384c.765.297 1.636.499 2.913.558 1.28.058 1.688.072 4.947.072s3.667-.014 4.947-.072c1.277-.06 2.148-.261 2.913-.558.789-.306 1.459-.717 2.126-1.384s1.079-1.336 1.384-2.126c.297-.765.499-1.636.558-2.913.058-1.28.072-1.688.072-4.947s-.014-3.667-.072-4.947c-.06-1.277-.261-2.148-.558-2.913-.306-.789-.717-1.459-1.384-2.126s-1.336-1.079-2.126-1.384c-.765-.297-1.636-.499-2.913-.558-1.28-.058-1.688-.072-4.947-.072zm0 2.16c3.204 0 3.584.012 4.85.07 1.17.053 1.805.249 2.227.415.56.217.96.477 1.38.896.42.42.679.819.896 1.38.166.422.362 1.057.415 2.227.058 1.266.07 1.646.07 4.85s-.012 3.584-.07 4.85c-.053 1.17-.249 1.805-.415 2.227-.217.56-.477.96-.896 1.38-.42.42-.819.679-1.38.896-.422.166-1.057.362-2.227.415-1.265.058-1.645.07-4.85.07s-3.584-.012-4.85-.07c-1.17-.053-1.805-.249-2.227-.415-.56-.217-.96-.477-1.38-.896-.42-.42-.679-.819-.896-1.38-.166-.422-.362-1.057-.415-2.227-.058-1.266-.07-1.646-.07-4.85s.012-3.584.07-4.85c.053-1.17.249-1.805.415-2.227.217-.56.477-.96.896-1.38.42-.42.819-.679 1.38-.896.422-.166 1.057-.362 2.227-.415 1.266-.058 1.646-.07 4.85-.07zm0 3.678c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.162 6.162 6.162 6.162-2.759 6.162-6.162-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4 2.209 0 4 1.791 4 4 0 2.21-1.791 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.645-1.44-1.44s.645-1.44 1.44-1.44c.795 0 1.44.645 1.44 1.44z"/>
                            </svg>
                        </a>
                        @endif
                        @if($footer?->twitter)
                        <a href="{{ $footer->twitter }}" class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center hover:bg-white/10 transition-all">
                            <svg class="w-5 h-5" fill="#FFFFFF" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        @endif
                        @if($footer?->tiktok)
                        <a href="{{ $footer->tiktok }}" class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center hover:bg-white/10 transition-all">
                            <svg class="w-5 h-5" fill="#FFFFFF" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                        </a>
                        @endif
                        @if($footer?->linkedin)
                        <a href="{{ $footer->linkedin }}" class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center hover:bg-white/10 transition-all">
                            <svg class="w-5 h-5" fill="#0A66C2" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.761 0 5-2.239 5-5v-14c0-2.761-2.239-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                        </a>
                        @endif
                    </div>
                </div>

                @foreach(\App\Models\LandingFooterLink::GROUPS as $groupKey => $groupLabel)
                <div>
                    <h3 class="text-white font-black uppercase tracking-widest text-sm mb-8">{{ $groupLabel }}</h3>
                    <ul class="space-y-4">
                        @foreach($footerLinks->get($groupKey, collect()) as $link)
                        <li><a href="{{ str_starts_with($link->link, '#') ? url('/' . $link->link) : $link->link }}" class="text-slate-400 hover:text-[#12b5a0] transition-colors duration-200 text-sm">{{ $link->title }}</a></li>
                        @endforeach
                    </ul>
                </div>
                @endforeach
            </div>

            <div class="border-t border-white/10 pt-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-slate-500 text-sm font-medium">{{ $footer?->copyright_text ?? '© ' . date('Y') . ' Tanaki Hub. All rights reserved.' }}</p>
                <div class="flex items-center gap-6">
                    <a href="{{ url('/privacy') }}" class="text-slate-500 hover:text-[#12b5a0] transition-colors duration-200 text-sm">Privacy Policy</a>
                    <a href="{{ url('/terms') }}" class="text-slate-500 hover:text-[#12b5a0] transition-colors duration-200 text-sm">Terms of Service</a>
                    <a href="{{ url('/cookies') }}" class="text-slate-500 hover:text-[#12b5a0] transition-colors duration-200 text-sm">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Smooth Scroll
        function goTo(id) {
            const element = document.getElementById(id);
            if (element) {
                const navHeight = 80;
                const position = element.getBoundingClientRect().top + window.pageYOffset - navHeight;
                window.scrollTo({ top: position, behavior: 'smooth' });
            }
        }

        // Operation pill → which of the 3 "Build Tanaki around your
        // operation" cards it highlights. Mirrors the same map in the blade
        // template. Multi-select; nothing is persisted or sent anywhere —
        // this only visually highlights the matching card(s), it doesn't
        // create or reveal new content.
        const operationCategoryMap = {
            fleet: ['fleet'],
            construction: ['construction'],
            quarry: ['quarry'],
            multi: ['fleet', 'construction', 'quarry'],
        };

        function toggleOperationPill(pill) {
            const nowSelected = pill.getAttribute('aria-pressed') !== 'true';
            pill.setAttribute('aria-pressed', nowSelected ? 'true' : 'false');
            pill.classList.toggle('operation-pill-selected', nowSelected);

            const selectedKeys = [...document.querySelectorAll('[data-operation-pill][aria-pressed="true"]')]
                .map((el) => el.dataset.operationPill);
            const highlighted = new Set(selectedKeys.flatMap((key) => operationCategoryMap[key] || []));
            document.querySelectorAll('[data-module-category]').forEach((card) => {
                card.classList.toggle('module-category-highlighted', highlighted.has(card.dataset.moduleCategory));
            });
        }

        function togglePlanFeatures(planId, button) {
            const extra = document.querySelector(`[data-extra-features="${planId}"]`);
            if (!extra) return;
            const nowHidden = !extra.classList.toggle('hidden');
            button.textContent = nowHidden ? 'View all features →' : 'Show fewer features ←';
        }

        // Platform Preview switcher — swaps which preview panel/tab is shown,
        // and auto-advances on a timer so it plays as a slideshow without
        // needing a click. Clicking a tab still works and just restarts the
        // timer from that slide.
        let platformPreviewTimer = null;

        function showPlatformPreview(index, isManual = false) {
            document.querySelectorAll('[data-preview-panel]').forEach((panel) => {
                const isActive = panel.dataset.previewPanel === String(index);
                panel.classList.toggle('hidden', !isActive);
                if (isActive) {
                    panel.querySelectorAll('.reveal').forEach((el) => el.classList.add('visible'));
                }
            });

            document.querySelectorAll('[data-preview-tab]').forEach((tab) => {
                const isActive = tab.dataset.previewTab === String(index);
                tab.classList.toggle('text-white', isActive);
                tab.classList.toggle('border-transparent', isActive);
                tab.classList.toggle('bg-white', !isActive);
                tab.classList.toggle('text-slate-600', !isActive);
                tab.classList.toggle('border-slate-200', !isActive);
                tab.style.backgroundColor = isActive ? '#2F4F4F' : '';
            });

            if (isManual) {
                startPlatformPreviewSlideshow(index);
            }
        }

        function startPlatformPreviewSlideshow(startIndex = 0) {
            const total = document.querySelectorAll('[data-preview-panel]').length;
            clearInterval(platformPreviewTimer);
            if (total <= 1 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }

            let current = startIndex;
            platformPreviewTimer = setInterval(() => {
                current = (current + 1) % total;
                showPlatformPreview(current);
            }, 5000);
        }

        // Mobile Menu Toggle
        function toggleMenu() {
            const menu = document.getElementById('mobile-menu');
            const openIcon = document.getElementById('icon-open');
            const closeIcon = document.getElementById('icon-close');
            
            menu.classList.toggle('open');
            openIcon.classList.toggle('hidden');
            closeIcon.classList.toggle('hidden');
        }

        // Navbar Shadow
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('navbar');
            if (window.scrollY > 10) {
                nav.style.boxShadow = '0 4px 24px rgba(0,0,0,.15)';
                nav.classList.add('border-white/10');
            } else {
                nav.style.boxShadow = 'none';
                nav.classList.remove('border-white/10');
            }
        });

        // Intersection Observer for Reveal
        const observerOptions = {
            threshold: 0.12,
            rootMargin: '0px 0px -40px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Initialize Observer
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

        // Start the Platform Preview slideshow (no-op if there's only one preview)
        startPlatformPreviewSlideshow();
    </script>
</body>
</html>