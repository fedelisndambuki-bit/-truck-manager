<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tanaki Hub - Smart Fleet Management</title>
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
                        fadeBadge: { '0%,100%': { boxShadow: '0 0 0 0 rgba(34,197,94,.45)' }, '50%': { boxShadow: '0 0 0 8px rgba(34,197,94,0)' } },
                        heroFadeUp: { 'from': { opacity: '0', transform: 'translateY(24px)' }, 'to': { opacity: '1', transform: 'none' } }
                    }
                }
            }
        }
    </script>
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(to bottom, #F8FAFC, #FFFFFF, #EFF6FF); }

        .text-grad-blue { background: linear-gradient(135deg, #0F172A, #2563EB, #0F172A); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .text-grad-purple { background: linear-gradient(135deg, #2563EB, #8B5CF6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .text-grad-green { background: linear-gradient(135deg, #22C55E, #10B981); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }

        .nav-glass { background: #1e293b; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
        .glass { background: rgba(255,255,255,.08); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,.18); }
        .cta-grad { background: #1e293b; }

        .reveal { opacity: 0; transform: translateY(28px); transition: opacity .65s ease, transform .65s ease; }
        .reveal.visible { opacity: 1; transform: none; }

        .hover-lift { transition: transform .25s ease, box-shadow .25s ease; }
        .hover-lift:hover { transform: translateY(-8px) scale(1.02); }

        .pricing-card { transition: transform .3s ease, box-shadow .3s ease; }
        .pricing-card:hover { transform: translateY(-12px) scale(1.02); }

        .bento-card { transition: transform .3s ease, box-shadow .3s ease; overflow: hidden; position: relative; }
        .bento-card .overlay { position: absolute; inset: 0; opacity: 0; transition: opacity .3s; }
        .bento-card:hover { transform: scale(1.04); box-shadow: 0 24px 40px rgba(0,0,0,.12); }
        .bento-card:hover .overlay { opacity: .08; }

        .phone-notch { position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 8rem; height: 1.75rem; background: #111827; border-radius: 0 0 1.5rem 1.5rem; z-index: 10; }
        #mobile-menu { display: none; }
        #mobile-menu.open { display: block; }

        @keyframes heroFadeUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: none; } }
        .hero-anim-1 { animation: heroFadeUp .6s 0s ease both; }
        .hero-anim-2 { animation: heroFadeUp .6s .12s ease both; }
        .hero-anim-3 { animation: heroFadeUp .6s .24s ease both; }
        .hero-anim-4 { animation: heroFadeUp .6s .36s ease both; }

        .blue-gradient { background: linear-gradient(135deg, #2563EB, #1D4ED8); }
        .green-gradient { background: linear-gradient(135deg, #22C55E, #10B981); }
    </style>
</head>
<body class="antialiased text-slate-900">

    <!-- NAVIGATION -->
    <nav id="navbar" class="nav-glass sticky top-0 z-50 border-b border-gray-200/10 transition-shadow">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <!-- Logo -->
            <div class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 cursor-pointer" onclick="window.scrollTo({top:0, behavior:'smooth'})">
                <img src="/images/tanaki-logo.png" alt="Tanaki Hub" class="h-9 w-auto">
            </div>

            <!-- Desktop Nav -->
            <div class="hidden md:flex items-center gap-8">
                <button onclick="goTo('features')" class="text-white/80 hover:text-white transition-colors font-medium text-sm">Features</button>
                <button onclick="goTo('mobile-app')" class="text-white/80 hover:text-white transition-colors font-medium text-sm">Mobile App</button>
                <button onclick="goTo('pricing')" class="text-white/80 hover:text-white transition-colors font-medium text-sm">Pricing</button>
            </div>

            <!-- Desktop Auth -->
            <div class="hidden md:flex items-center gap-6">
                <a href="{{ route('login') }}" class="text-white/80 hover:text-white transition-colors font-medium text-sm">Login</a>
                <a href="{{ route('register') }}" class="blue-gradient text-white px-6 py-2.5 rounded-full font-bold text-sm hover:shadow-xl hover:shadow-blue-500/30 hover:scale-105 transition-all">Get Started Free</a>
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
                <button onclick="goTo('features'); toggleMenu()" class="text-white/80 text-lg font-medium text-left">Features</button>
                <button onclick="goTo('mobile-app'); toggleMenu()" class="text-white/80 text-lg font-medium text-left">Mobile App</button>
                <button onclick="goTo('pricing'); toggleMenu()" class="text-white/80 text-lg font-medium text-left">Pricing</button>
                <div class="h-px bg-white/10 my-2"></div>
                <a href="{{ route('login') }}" class="text-white/80 text-lg font-medium">Login</a>
                <a href="{{ route('register') }}" class="blue-gradient text-white py-4 rounded-2xl text-center font-bold text-lg shadow-lg">Get Started Free</a>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="py-20 md:py-32 relative overflow-hidden">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-blue-400/10 blur-[120px] rounded-full animate-blob1"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-purple-400/10 blur-[120px] rounded-full animate-blob2"></div>

        <div class="max-w-7xl mx-auto px-6 text-center">
            <div class="hero-anim-1 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-100 mb-8">
                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1a1 1 0 10-2 0v1a1 1 0 102 0zM14.243 14.243a1 1 0 10-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM16 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1z"/></svg>
                <span class="text-sm font-bold text-blue-700 tracking-wide uppercase">Launching the Future of Fleet Management</span>
            </div>

            <h1 class="hero-anim-2 text-5xl md:text-8xl font-black mb-8 leading-[1.1] tracking-tight">
                <span class="text-grad-blue">Smart Fleet,</span><br>
                <span class="text-[#0F172A]">Smarter Business</span>
            </h1>

            <p class="hero-anim-3 max-w-2xl mx-auto text-xl md:text-2xl text-[#64748B] mb-12 leading-relaxed">
                Transform your transport operations with AI-powered fleet intelligence. Manage everything from trucks to drivers, all in one beautiful platform.
            </p>

            <div class="hero-anim-4 flex flex-col md:flex-row items-center justify-center gap-4 mb-24">
                <a href="{{ route('register') }}" class="group blue-gradient text-white px-10 py-5 rounded-2xl font-bold text-lg flex items-center gap-2 hover:shadow-2xl hover:shadow-blue-500/40 hover:-translate-y-1 transition-all">
                    Start Free Trial
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <button onclick="goTo('mobile-app')" class="w-full md:w-auto px-10 py-5 rounded-2xl font-bold text-lg border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white transition-all">
                    See Mobile App
                </button>
            </div>

            <!-- Stats Grid -->
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <div class="group bg-white rounded-[2.5rem] p-8 shadow-xl shadow-slate-200/50 hover-lift relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-cyan-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-500 rounded-2xl flex items-center justify-center text-white mb-6 mx-auto shadow-lg">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <div class="text-4xl font-black text-slate-900 mb-1">10k+</div>
                    <div class="text-slate-500 font-medium">Active Fleets</div>
                </div>
                <div class="group bg-white rounded-[2.5rem] p-8 shadow-xl shadow-slate-200/50 hover-lift relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-pink-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-pink-500 rounded-2xl flex items-center justify-center text-white mb-6 mx-auto shadow-lg">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div class="text-4xl font-black text-slate-900 mb-1">50k+</div>
                    <div class="text-slate-500 font-medium">Happy Drivers</div>
                </div>
                <div class="group bg-white rounded-[2.5rem] p-8 shadow-xl shadow-slate-200/50 hover-lift relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-green-500/10 to-emerald-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-500 rounded-2xl flex items-center justify-center text-white mb-6 mx-auto shadow-lg">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    </div>
                    <div class="text-4xl font-black text-slate-900 mb-1">25+</div>
                    <div class="text-slate-500 font-medium">Countries</div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES GRID SECTION -->
    <section id="features" class="py-24 bg-white relative">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-20 reveal">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-blue-50 border border-blue-100 mb-4">
                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1a1 1 0 10-2 0v1a1 1 0 102 0zM14.243 14.243a1 1 0 10-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM16 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1z"/></svg>
                    <span class="text-sm font-bold text-blue-700 uppercase tracking-widest">Powerful Features</span>
                </div>
                <h2 class="text-4xl md:text-6xl font-black text-slate-900 mb-6">
                    Everything You Need,<br>
                    <span class="text-grad-purple">Nothing You Don't</span>
                </h2>
            </div>

            <div id="features-grid" class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- JS Inject -->
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
                        <span class="text-sm font-bold text-green-400 uppercase tracking-widest">Mobile App for Drivers</span>
                    </div>
                    <h2 class="text-4xl md:text-6xl font-black text-white mb-8">
                        Introducing<br>
                        <span class="text-grad-green">Shift Hub Driver</span>
                    </h2>
                    <p class="text-xl text-slate-300 mb-10 leading-relaxed">
                        Empower your drivers with a powerful mobile app designed to streamline communication, automate logs, and maximize efficiency on the road.
                    </p>

                    <div id="app-features-list" class="space-y-4 mb-12">
                        <!-- JS Inject -->
                    </div>

                    <div class="flex flex-wrap gap-4">
                        <!-- Google Play Badge -->
                        <a href="#" class="hover:scale-105 transition-transform block">
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
                        </a>
                        <!-- App Store Badge -->
                        <a href="#" class="hover:scale-105 transition-transform block">
                            <svg width="185" height="60" viewBox="0 0 185 60" xmlns="http://www.w3.org/2000/svg">
                                <rect width="185" height="60" rx="12" fill="black"/>
                                <path d="M38.5 28.5C38.5 24.5 41.5 22.5 41.5 22.5C39.5 20.5 37.5 19.5 35.5 19.5C33.5 19.5 31.5 20.5 30.5 20.5C29.5 20.5 27.5 19.5 25.5 19.5C22.5 19.5 18.5 22.5 18.5 28.5C18.5 34.5 22.5 43.5 25.5 43.5C27.5 43.5 28.5 42.5 30.5 42.5C32.5 42.5 33.5 43.5 35.5 43.5C38.5 43.5 42.5 34.5 42.5 34.5C42.5 34.5 38.5 32.5 38.5 28.5ZM33.5 17.5C34.5 16.5 35.5 14.5 35.5 12.5C35.5 12.5 33.5 12.5 32.5 13.5C31.5 14.5 30.5 16.5 30.5 18.5C30.5 18.5 32.5 18.5 33.5 17.5Z" fill="white"/>
                                <text x="55" y="25" fill="white" fill-opacity="0.75" font-size="10" font-family="Arial">DOWNLOAD ON THE</text>
                                <text x="55" y="45" fill="white" font-size="18" font-weight="bold" font-family="Arial">App Store</text>
                            </svg>
                        </a>
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
                                <img src="/images/app-screenshot.jpg" alt="App Screenshot" class="w-full h-full object-cover object-top rounded-[2.5rem]">
                            </div>
                        </div>

                        <!-- Floating Badges -->
                        <div class="absolute -top-4 -right-8 bg-white p-4 rounded-2xl shadow-2xl animate-float-up z-20 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-black text-slate-900">4.9 Rating</div>
                                <div class="text-xs text-slate-500 font-bold uppercase tracking-tighter">Top Driver App</div>
                            </div>
                        </div>

                        <div class="absolute -bottom-6 -left-8 green-gradient p-4 rounded-2xl shadow-2xl animate-float-down z-20 flex items-center gap-3 text-white">
                            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.381z" clip-rule="evenodd"/></svg>
                            </div>
                            <div>
                                <div class="text-sm font-black">Fast Payouts</div>
                                <div class="text-xs text-white/80 font-bold uppercase tracking-tighter">Instant Settlements</div>
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
            <div class="text-center mb-16 reveal">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-green-50 border border-green-100 mb-4">
                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.184c-.622.117-1.195.342-1.676.662C6.602 13.235 6 14.01 6 15c0 .99.602 1.765 1.324 2.246A4.535 4.535 0 009 17.908V19a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662c.722-.481 1.324-1.256 1.324-2.246 0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 13.092v-1.184c.622-.117 1.195-.342 1.676-.662C13.398 10.765 14 9.99 14 9c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 6.092V5z" clip-rule="evenodd"/></svg>
                    <span class="text-sm font-bold text-green-700 uppercase tracking-widest">Simple Pricing</span>
                </div>
                <h2 class="text-4xl md:text-6xl font-black text-slate-900 mb-4">Choose Your Perfect Plan</h2>
                <p class="text-xl text-slate-500">Start free, scale as you grow. No hidden fees.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto items-stretch">
                <!-- Free Plan -->
                <div class="pricing-card bg-white border-2 border-slate-100 rounded-[2.5rem] p-10 flex flex-col reveal" style="transition-delay: 0ms">
                    <div class="mb-8">
                        <div class="text-lg font-bold text-slate-900 mb-2">Growth Plan</div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-5xl font-black text-slate-900">$29</span>
                            <span class="text-slate-500 font-medium">/ month</span>
                        </div>
                    </div>
                    <ul class="space-y-4 mb-10 flex-grow">
                        <li class="flex items-center gap-3 text-slate-600 font-medium">
                            <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            Up to 50 vehicles
                        </li>
                        <li class="flex items-center gap-3 text-slate-600 font-medium">
                            <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            GPS tracking
                        </li>
                        <li class="flex items-center gap-3 text-slate-600 font-medium">
                            <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            Driver payroll
                        </li>
                        <li class="flex items-center gap-3 text-slate-600 font-medium">
                            <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            Financial reports
                        </li>
                        <li class="flex items-center gap-3 text-slate-600 font-medium">
                            <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            Invoice module
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="blue-gradient text-white py-4 rounded-2xl text-center font-bold text-lg hover:shadow-xl hover:shadow-blue-500/20 transition-all">Start Plan</a>
                </div>

                <!-- Featured Plan -->
                <div class="pricing-card green-gradient rounded-[2.5rem] p-10 flex flex-col text-white shadow-2xl shadow-green-500/30 border-4 border-green-400 relative reveal" style="transition-delay: 100ms">
                    <div class="absolute -top-5 left-1/2 -translate-x-1/2 bg-white text-green-600 px-6 py-2 rounded-full font-black text-sm uppercase tracking-widest shadow-lg animate-fade-badge flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                        Most Popular
                    </div>
                    <div class="mb-8">
                        <div class="text-lg font-bold text-white/90 mb-2">Free Trial</div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-5xl font-black">$0</span>
                            <span class="text-white/80 font-medium">/ 14 days</span>
                        </div>
                    </div>
                    <ul class="space-y-4 mb-10 flex-grow">
                        <li class="flex items-center gap-3 font-medium">
                            <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            Up to 5 vehicles
                        </li>
                        <li class="flex items-center gap-3 font-medium">
                            <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            Driver management
                        </li>
                        <li class="flex items-center gap-3 font-medium">
                            <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            Basic reporting
                        </li>
                        <li class="flex items-center gap-3 font-medium">
                            <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            Settlement tracking
                        </li>
                        <li class="flex items-center gap-3 font-medium">
                            <div class="w-6 h-6 rounded-full bg-white/20 flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            Mobile app access
                        </li>
                    </ul>
                    <a href="{{ route('register') }}" class="bg-white text-green-600 py-4 rounded-2xl text-center font-bold text-lg hover:scale-105 transition-all shadow-xl">Start Free Trial</a>
                </div>

                <!-- Enterprise Plan -->
                <div class="pricing-card bg-[#0F172A] rounded-[2.5rem] p-10 flex flex-col text-white reveal" style="transition-delay: 200ms">
                    <div class="mb-8">
                        <div class="text-lg font-bold text-white/90 mb-2">Enterprise</div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-5xl font-black text-white">Custom</span>
                        </div>
                    </div>
                    <ul class="space-y-4 mb-10 flex-grow">
                        <li class="flex items-center gap-3 font-medium text-slate-300">
                            <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            Unlimited vehicles
                        </li>
                        <li class="flex items-center gap-3 font-medium text-slate-300">
                            <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            Advanced analytics
                        </li>
                        <li class="flex items-center gap-3 font-medium text-slate-300">
                            <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            AI fleet predictions
                        </li>
                        <li class="flex items-center gap-3 font-medium text-slate-300">
                            <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            Dedicated support
                        </li>
                        <li class="flex items-center gap-3 font-medium text-slate-300">
                            <div class="w-6 h-6 rounded-full bg-white/10 flex items-center justify-center text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            SLA guarantee
                        </li>
                    </ul>
                    <button onclick="goTo('footer')" class="bg-white text-[#0F172A] py-4 rounded-2xl text-center font-bold text-lg flex items-center justify-center gap-2 hover:bg-slate-100 transition-all">
                        Contact Sales
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- PLATFORM PREVIEW -->
    <section class="py-24 bg-gradient-to-br from-gray-50 to-blue-50">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <h2 class="text-4xl md:text-6xl font-black mb-6 leading-tight">
                One Platform,<br>
                <span class="text-grad-purple">Infinite Possibilities</span>
            </h2>
            <p class="text-xl text-slate-500 mb-16 max-w-2xl mx-auto">Your entire fleet operation in one unified workspace. Powerful tools for modern transport businesses.</p>

            <!-- Browser Mockup -->
            <div class="reveal relative max-w-6xl mx-auto p-[2px] rounded-[1.5rem] bg-gradient-to-r from-blue-500 to-green-500 shadow-2xl">
                <div class="bg-white rounded-[1.4rem] overflow-hidden">
                    <!-- Toolbar -->
                    <div class="bg-[#1e293b] px-6 py-3 flex items-center justify-between">
                        <div class="flex gap-2">
                            <div class="w-3 h-3 rounded-full bg-red-400"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                            <div class="w-3 h-3 rounded-full bg-green-400"></div>
                        </div>
                        <div class="bg-white/10 px-6 py-1.5 rounded-full text-[10px] text-white/60 font-medium tracking-wide">app.tanakihub.com/dashboard</div>
                        <div class="w-10"></div>
                    </div>
                    <!-- Content -->
                    <img src="/images/dashboard-screenshot.jpg" alt="Dashboard Preview" class="w-full max-h-[520px] object-cover object-top">
                </div>
            </div>

            <div class="flex flex-wrap justify-center gap-3 mt-12 reveal">
                <span class="bg-white border border-slate-200 text-slate-600 px-6 py-2 rounded-full font-bold text-sm hover:border-blue-500 hover:text-blue-600 transition-all cursor-default">Fleet Dashboard</span>
                <span class="bg-white border border-slate-200 text-slate-600 px-6 py-2 rounded-full font-bold text-sm hover:border-blue-500 hover:text-blue-600 transition-all cursor-default">Driver Cards</span>
                <span class="bg-white border border-slate-200 text-slate-600 px-6 py-2 rounded-full font-bold text-sm hover:border-blue-500 hover:text-blue-600 transition-all cursor-default">Settlements</span>
                <span class="bg-white border border-slate-200 text-slate-600 px-6 py-2 rounded-full font-bold text-sm hover:border-blue-500 hover:text-blue-600 transition-all cursor-default">Invoices</span>
                <span class="bg-white border border-slate-200 text-slate-600 px-6 py-2 rounded-full font-bold text-sm hover:border-blue-500 hover:text-blue-600 transition-all cursor-default">GPS Tracking</span>
                <span class="bg-white border border-slate-200 text-slate-600 px-6 py-2 rounded-full font-bold text-sm hover:border-blue-500 hover:text-blue-600 transition-all cursor-default">AI Insights</span>
            </div>
        </div>
    </section>

    <!-- CTA SECTION -->
    <section class="py-24 bg-[#1e293b] relative overflow-hidden">
        <div class="absolute top-0 left-0 w-[500px] h-[500px] bg-white/5 blur-[120px] rounded-full animate-spin-slow"></div>
        <div class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-white/5 blur-[120px] rounded-full animate-spin-slow" style="animation-direction: reverse;"></div>

        <div class="max-w-4xl mx-auto px-6 text-center relative z-10 reveal">
            <h2 class="text-4xl md:text-7xl font-black text-white mb-8">Ready to Transform Your Fleet?</h2>
            <p class="text-xl text-white/80 mb-12">Join 10,000+ companies already using Tanaki Hub to drive their business forward.</p>
            <div class="flex flex-col md:flex-row items-center justify-center gap-6">
                <a href="{{ route('register') }}" class="group bg-white text-blue-600 px-12 py-5 rounded-2xl font-black text-xl flex items-center gap-3 hover:scale-105 transition-all shadow-2xl">
                    Start Free Trial
                    <svg class="w-6 h-6 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </a>
                <button onclick="goTo('footer')" class="px-12 py-5 rounded-2xl font-bold text-xl text-white border-2 border-white/20 backdrop-blur-md hover:bg-white/10 transition-all flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    Talk to Sales
                </button>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer id="footer" class="py-20 bg-[#0F172A] text-white">
        <div class="max-w-7xl mx-auto px-6">
            <div class="grid md:grid-cols-4 gap-16 mb-20">
                <div class="md:col-span-1">
                    <div class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 mb-8">
                        <img src="/images/tanaki-logo.png" alt="Tanaki Hub" class="h-9 w-auto">
                    </div>
                    <p class="text-slate-400 mb-8 leading-relaxed">
                        The smart fleet intelligence platform for modern transport companies. Scale your operations with confidence.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center hover:bg-white/10 hover:text-blue-400 transition-all text-slate-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center hover:bg-white/10 hover:text-blue-600 transition-all text-slate-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.761 0 5-2.239 5-5v-14c0-2.761-2.239-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center hover:bg-white/10 hover:text-white transition-all text-slate-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.43.372.823 1.102.823 2.222 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="text-white font-black uppercase tracking-widest text-sm mb-8">Product</h4>
                    <ul class="space-y-4">
                        <li><button onclick="goTo('features')" class="text-slate-400 hover:text-white transition-colors text-sm">Features</button></li>
                        <li><button onclick="goTo('mobile-app')" class="text-slate-400 hover:text-white transition-colors text-sm">Mobile App</button></li>
                        <li><button onclick="goTo('pricing')" class="text-slate-400 hover:text-white transition-colors text-sm">Pricing</button></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors text-sm">Integrations</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors text-sm">API</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-black uppercase tracking-widest text-sm mb-8">Company</h4>
                    <ul class="space-y-4">
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors text-sm">About</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors text-sm">Careers</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors text-sm">Blog</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors text-sm">Contact</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-black uppercase tracking-widest text-sm mb-8">Legal</h4>
                    <ul class="space-y-4">
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors text-sm">Privacy Policy</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors text-sm">Terms</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors text-sm">Security</a></li>
                        <li><a href="#" class="text-slate-400 hover:text-white transition-colors text-sm">GDPR</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-white/5 pt-10 flex flex-col md:flex-row items-center justify-between gap-6">
                <p class="text-slate-500 text-sm font-medium">© {{ date('Y') }} Tanaki Hub. All rights reserved.</p>
                <p class="text-slate-500 text-sm font-medium">Made with ❤️ for the transport industry</p>
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

        // Features Data
        const features = [
            {
                bgColor: '#2F4F4F',
                title: 'Fleet Tracking',
                desc: 'Real-time GPS tracking with live updates and route optimization for your entire fleet.',
                icon: 'M8 7H5a2 2 0 00-2 2v9a1 1 0 001 1h1M17 7h2.293a1 1 0 01.707.293l3.414 3.414A1 1 0 0124 11.414V18a2 2 0 01-2 2h-1M6 18a3 3 0 106 0 3 3 0 00-6 0zm11 0a3 3 0 106 0 3 3 0 00-6 0zM13 13V8a1 1 0 00-1-1H9m4 12V7h1M12 11h2'
            },
            {
                bgColor: 'linear-gradient(to bottom right, #8b5cf6, #ec4899)',
                title: 'Driver Management',
                desc: 'Complete driver profiles, performance tracking, payroll and scheduling in one place.',
                icon: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'
            },
            {
                bgColor: 'linear-gradient(to bottom right, #10b981, #059669)',
                title: 'Financial Settlements',
                desc: 'Automated payments, fuel advances and complete financial transparency with full audit trails.',
                icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
            },
            {
                bgColor: 'linear-gradient(to bottom right, #f97316, #dc2626)',
                title: 'GPS Integration',
                desc: 'Advanced geofencing, delivery verification and route deviation alerts automatically.',
                icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z'
            },
            {
                bgColor: 'linear-gradient(to bottom right, #6366f1, #8b5cf6)',
                title: 'AI Fleet Insights',
                desc: 'Predictive analytics to optimize routes, reduce fuel costs and flag maintenance needs early.',
                icon: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'
            },
            {
                bgColor: 'linear-gradient(to bottom right, #ec4899, #f43f5e)',
                title: 'Automated Invoicing',
                desc: 'Professional invoices with integrated payment tracking and one-click finance exports.',
                icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
            }
        ];

        // App Features Data
        const appFeats = [
            { icon: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7', text: 'Real-time GPS navigation & route guidance' },
            { icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', text: 'Smart shift scheduling & time tracking' },
            { icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', text: 'Instant notifications for new assignments' },
            { icon: 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', text: 'View earnings & settlements in real-time' }
        ];

        // Render Features
        const grid = document.getElementById('features-grid');
        features.forEach((f, i) => {
            grid.innerHTML += `
                <div class="hover-lift group bg-gradient-to-br from-white to-gray-50 rounded-[2.5rem] p-8 shadow-xl shadow-slate-200/50 border border-slate-100 reveal" style="transition-delay: ${i * 80}ms">
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white mb-8 shadow-lg group-hover:scale-110 transition-transform" style="background: ${f.bgColor} !important">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${f.icon}"/></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-4">${f.title}</h3>
                    <p class="text-slate-500 font-medium leading-relaxed">${f.desc}</p>
                </div>
            `;
        });

        // Render App Features
        const appList = document.getElementById('app-features-list');
        appFeats.forEach(f => {
            appList.innerHTML += `
                <div class="glass rounded-2xl p-4 flex items-center gap-4 border border-white/10 hover:bg-white/15 transition-all">
                    <div class="w-12 h-12 green-gradient rounded-xl flex items-center justify-center text-white shrink-0 shadow-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${f.icon}"/></svg>
                    </div>
                    <span class="text-white font-semibold">${f.text}</span>
                </div>
            `;
        });

        // Initialize Observer
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    </script>
</body>
</html>