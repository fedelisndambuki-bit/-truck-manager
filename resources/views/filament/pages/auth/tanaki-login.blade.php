<div class="tk-split min-h-screen w-full">
    @vite(['resources/css/auth.css', 'resources/js/app.js'])

    {{-- ══════════════════════════ LEFT — HERO / MARKETING PANEL ══════════════════════════ --}}
    <div class="tk-hero relative flex flex-col overflow-y-auto pb-10 pl-6 pr-6 pt-10 text-white lg:pb-12 lg:pl-20 lg:pr-16 lg:pt-[70px]">
        <x-auth.logo layout="horizontal" :mark-size="44" />

        <div class="mt-8 max-w-[540px]">
            <h1 class="text-[52px] font-extrabold leading-[1.12] tracking-tight">
                One Platform.<br>
                <span class="text-tanaki-teal">Every Operation.</span>
            </h1>

            <p class="mt-4 max-w-[480px] text-base leading-relaxed text-slate-300">
                Manage your fleet, construction projects, quarry operations, workshops, fuel, weighbridge, procurement, HR, finance and assets from one intelligent industrial operating platform.
            </p>

            {{-- Module cards are part of the full desktop hero only — on
                 mobile/tablet they're dropped so the hero stays a short
                 header instead of pushing the login form far down the page. --}}
            <div class="tk-module-grid mt-7 max-w-[560px]">
                <x-auth.feature-card title="Fleet Management" path='<path d="M3 17h13V7H3v10zm13-7h3l3 4v3h-6v-7zM6 20a2 2 0 100-4 2 2 0 000 4zm12 0a2 2 0 100-4 2 2 0 000 4z"/>' />
                <x-auth.feature-card title="Construction Management" path='<path d="M6 21V9l6-4 6 4v12M9 21v-6h6v6"/>' />
                <x-auth.feature-card title="Quarry Operations" path='<path d="M3 20l6-11 4 7 3-5 5 9H3z"/>' />
                <x-auth.feature-card title="Workshop Management" path='<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.1-3.1a5 5 0 01-6.6 6.6L5.4 21.4a2 2 0 01-2.8-2.8L11.8 9.9a5 5 0 016.6-6.6l-3.1 3.1z"/>' />
                <x-auth.feature-card title="Weighbridge" path='<path d="M12 3v18M5 7l-3 6a3 3 0 006 0l-3-6zm14 0l-3 6a3 3 0 006 0l-3-6zM5 7h14"/>' />
                <x-auth.feature-card title="Fuel Management" path='<path d="M3 22V6a2 2 0 012-2h6a2 2 0 012 2v16M3 22h10m5-13h1.5a1.5 1.5 0 011.5 1.5V18a1.5 1.5 0 01-3 0V13l-2-2M5 10h6"/>' />
                <x-auth.feature-card title="Finance & Accounting" path='<circle cx="12" cy="12" r="9"/><path d="M12 7v10M15 9.5c0-1.4-1.3-2.5-3-2.5s-3 1-3 2.3c0 3 6 1.4 6 4.4 0 1.3-1.3 2.3-3 2.3s-3-1.1-3-2.5"/>' />
                <x-auth.feature-card title="HR & Payroll" path='<circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c.6-3.4 3.2-5.6 6.5-5.6s5.9 2.2 6.5 5.6"/><circle cx="17.5" cy="8.5" r="2.6"/><path d="M16 14.7c2.6.4 4.4 2.2 4.9 5.3"/>' />
            </div>
        </div>

        {{-- Flexible spacer — pushes the dashboard preview + quick stats down
             toward the bottom of the panel via Flexbox, never via absolute
             positioning, so the block naturally reflows on shorter viewports
             instead of overlapping the heading above it. Hidden below lg
             along with the preview itself, for the same reason as the
             module grid above: keeps the mobile hero short. --}}
        <div class="hidden min-h-6 flex-1 lg:block"></div>

        <div class="hidden max-w-[560px] lg:block">
            <x-auth.dashboard-preview />
            <x-auth.foot-strip />
        </div>
    </div>

    {{-- ══════════════════════════ RIGHT — AUTH PANEL ══════════════════════════ --}}
    <div class="flex flex-col bg-tanaki-bg-right lg:h-screen">
        {{-- flex-1 + centered: the card centers within the space ABOVE the
             footer, so the footer below can sit pinned at the very bottom
             instead of floating with it in the middle of the column.
             overflow-y-auto is a safety net, not the design: on any normal
             desktop height the card fits with room to spare and this never
             engages. On a genuinely short window it lets the card area
             scroll on its own, so content degrades gracefully instead of
             silently clipping — the footer sits outside this wrapper and
             stays pinned and reachable either way. --}}
        <div class="flex flex-1 items-center justify-center overflow-y-auto px-6 py-8 lg:px-8 lg:py-1">
            <div class="tk-auth-card my-auto flex w-full max-w-[410px] flex-col gap-6 rounded-[20px] bg-white px-9 py-9">
                <x-auth.logo layout="stacked" :mark-size="58" />

                <div class="text-center">
                    <h2 class="text-[27px] font-bold leading-tight text-tanaki-ink">Welcome Back</h2>
                    <p class="mt-1 text-sm text-tanaki-grey">Sign in to continue to your account.</p>
                </div>

            {{-- Filament's own authentication form — id, wire:submit, and action set
                 preserved exactly so validation, rate limiting, and the
                 HasCaseInsensitiveLogin / industry-based redirect logic in
                 App\Filament\Pages\Auth\Login keep working unmodified. --}}
            <div class="tk-auth-form">
                <x-filament-panels::form id="form" wire:submit="authenticate">
                    {{ $this->form }}

                    <x-filament-panels::form.actions
                        :actions="$this->getCachedFormActions()"
                        :full-width="$this->hasFullWidthFormActions()"
                    />
                </x-filament-panels::form>
            </div>

            @php
                $googleEnabled = filled(config('services.google.client_id'));
                $microsoftEnabled = filled(config('services.microsoft.client_id'));
            @endphp

            @if ($googleEnabled || $microsoftEnabled)
                <div>
                    <div class="mb-6 flex items-center gap-3 text-[11px] font-semibold uppercase tracking-wider text-tanaki-grey-light">
                        <span class="h-px flex-1 bg-tanaki-border"></span>
                        or continue with
                        <span class="h-px flex-1 bg-tanaki-border"></span>
                    </div>

                    <div class="tk-oauth-grid">
                        @if ($googleEnabled)
                            <x-auth.social-button provider="google" label="Continue with Google" />
                        @endif

                        @if ($microsoftEnabled)
                            <x-auth.social-button provider="microsoft" label="Continue with Microsoft" />
                        @endif
                    </div>
                </div>
            @endif

            {{-- Security note --}}
            <div class="flex items-center justify-center gap-2">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4 shrink-0 text-tanaki-teal">
                    <path d="M12 2l8 3v6c0 5-3.4 8.7-8 10-4.6-1.3-8-5-8-10V5l8-3z" />
                </svg>
                <div class="text-left leading-tight">
                    <div class="text-[12px] font-bold text-tanaki-ink">Enterprise-grade security</div>
                    <div class="text-[10.5px] text-tanaki-grey-light">256-bit encrypted connection</div>
                </div>
            </div>
        </div>
        </div>

        {{-- Footer is a direct sibling of the centered-card wrapper above,
             not nested inside it — that's what lets it sit pinned at the
             bottom of the full-height column via normal flex flow, instead
             of being vertically centered together with the card as one
             group. --}}
        <div class="flex shrink-0 flex-wrap items-center justify-center gap-4 px-8 pb-4 text-center text-[11.5px] text-tanaki-grey-light">
            <span>&copy; {{ now()->year }} Tanaki Technologies Ltd.</span>
            <span>Version 1.0.0</span>
            <a href="{{ url('/privacy') }}" class="hover:text-tanaki-grey">Privacy</a>
            <a href="{{ url('/terms') }}" class="hover:text-tanaki-grey">Terms</a>
            <a href="{{ url('/support') }}" class="hover:text-tanaki-grey">Support</a>
        </div>
    </div>
</div>
