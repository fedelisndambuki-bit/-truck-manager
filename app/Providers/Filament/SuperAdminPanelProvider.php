<?php

namespace App\Providers\Filament;

use App\Filament\SuperAdmin\Pages\Auth\Login;
use App\Filament\SuperAdmin\Pages\Auth\SuperAdminRegister;
use App\Filament\SuperAdmin\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SuperAdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentAsset::register([
            Css::make('filament-custom-styles', asset('css/filament-custom.css')),
        ]);

        $default = \App\Models\PlatformSetting::getSetting('default_currency', 'KES');
        $currency = session('currency', $default);
        \Filament\Tables\Table::$defaultCurrency = $currency;
        \Filament\Infolists\Infolist::$defaultCurrency = $currency;
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('super-admin')
            ->path('super-admin')
            ->authGuard('super_admin')
            ->login(Login::class)
            ->passwordReset()
            ->registration(SuperAdminRegister::class)
            ->brandName(\App\Models\PlatformSetting::getSetting('super_admin_platform_name', 'Tanaki Control Room'))
            ->favicon(asset('images/brand/tanaki-hub-icon-light.png'))
            ->sidebarCollapsibleOnDesktop()
            ->breadcrumbs(true)
            ->colors([
                'primary' => '#4A6D7C',
                'danger' => '#dc2626',
                'success' => '#059669',
                'warning' => '#d97706',
            ])
            ->maxContentWidth(\Filament\Support\Enums\MaxWidth::Full)
            ->discoverResources(in: app_path('Filament/SuperAdmin/Resources'), for: 'App\\Filament\\SuperAdmin\\Resources')
            ->discoverPages(in: app_path('Filament/SuperAdmin/Pages'), for: 'App\\Filament\\SuperAdmin\\Pages')
            ->discoverWidgets(in: app_path('Filament/SuperAdmin/Widgets'), for: 'App\\Filament\\SuperAdmin\\Widgets')
            ->pages([
                Dashboard::class,
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => '
                <link rel="preconnect" href="https://fonts.googleapis.com">
                <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@700;800&display=swap" rel="stylesheet">
                <style>
                    /* Notification Dropdown Fonts */
                    .notif-dropdown, .bell-btn { font-family: "DM Mono", monospace; }
                    .nd-title, .brand-name { font-family: "Syne", sans-serif; }

                    /* 1. Sidebar Color - DarkSlateGrey */
                    .fi-sidebar, 
                    aside.fi-sidebar, 
                    .fi-sidebar nav, 
                    .fi-sidebar-content, 
                    .fi-sidebar-nav {
                        background-color: #2f4f4f !important;
                    }

                    /* Ensure all text/icons inside the sidebar are white for contrast, except active ones */
                    .fi-sidebar *:not(.fi-sidebar-item-active *) {
                        color: #ffffff !important;
                        transition: color 0.2s ease !important;
                    }

                    /* Sidebar hover effect & Active state - Blue text/icon */
                    .fi-sidebar-item:hover *, 
                    .fi-sidebar-group:hover *,
                    .fi-sidebar-item-button:hover *,
                    aside.fi-sidebar a:hover *,
                    .fi-sidebar-item-active *,
                    .fi-sidebar-item-active:hover * {
                        color: #3b82f6 !important; /* Blue color */
                    }

                    /* Brand Name Consistency (Ignore Light/Dark Mode) */
                    .fi-logo, 
                    .tm-brand-name, 
                    .fi-sidebar-header .fi-sidebar-brand,
                    .fi-topbar .fi-logo {
                        color: #ffffff !important;
                        opacity: 1 !important;
                    }

                    /* 7. Topbar & Brand Area Consistency (Ignore Light/Dark Mode) */
                    header.fi-topbar,
                    .fi-topbar,
                    .fi-topbar > div,
                    .fi-topbar nav,
                    .fi-sidebar-header {
                        background-color: #000000 !important;
                        background: #000000 !important;
                        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
                    }
                    
                    /* Ensure all topbar and brand area icons, text, and buttons are white */
                    header.fi-topbar *, 
                    .fi-topbar *, 
                    .fi-sidebar-header *,
                    .fi-topbar button, 
                    .fi-topbar a,
                    .fi-topbar .fi-dropdown-trigger * {
                        color: #ffffff !important;
                        fill: #ffffff !important;
                    }
                    
                    /* Prevent light mode from overriding text colors */
                    .fi-topbar .fi-topbar-item-label,
                    .fi-topbar .fi-topbar-item-icon,
                    .tm-brand-name {
                        color: #ffffff !important;
                    }

                    /* 2. Hide Scrollbars globally and specifically in the sidebar */
                    * {
                        -ms-overflow-style: none !important;
                        scrollbar-width: none !important;
                    }
                    ::-webkit-scrollbar {
                        display: none !important;
                        width: 0 !important;
                        height: 0 !important;
                    }
                    .fi-sidebar, .fi-sidebar nav, .fi-sidebar-content {
                        overflow-y: auto !important; /* Allow scrolling but hide scrollbar */
                        scrollbar-width: none !important;
                    }

                    /* 3. Force full-screen width & reduce empty space */
                    .fi-layout, 
                    .fi-main,
                    .fi-main-ctn,
                    .fi-page,
                    .fi-page-content,
                    .fi-page-ctn {
                        max-width: none !important;
                        width: 100% !important;
                    }

                    .fi-layout > .fi-main {
                        padding-left: 0 !important;
                        padding-right: 0 !important;
                        padding-top: 0.5rem !important;
                    }

                    .fi-page {
                        padding-left: 1rem !important;
                        padding-right: 1rem !important;
                    }

                    /* 4. Button Customization */
                    /* Create / New / Primary Buttons */
                    .fi-btn.fi-color-primary,
                    .fi-btn-color-primary,
                    .fi-ac-btn-action[type="submit"],
                    .fi-modal-footer .fi-btn[type="submit"] {
                        background-color: #2f4f4f !important;
                        border-color: #2f4f4f !important;
                        color: #ffffff !important;
                    }
                    .fi-btn.fi-color-primary:hover,
                    .fi-btn-color-primary:hover {
                        background-color: #3b6363 !important;
                    }

                    /* Cancel / Gray / Secondary Buttons */
                    .fi-btn.fi-color-gray:not([title*="Refresh" i]):not([aria-label*="Refresh" i]):not([wire\:click*="refresh" i]):not([wire\:click*="createAnother"]),
                    .fi-btn-color-gray:not([title*="Refresh" i]):not([aria-label*="Refresh" i]):not([wire\:click*="refresh" i]):not([wire\:click*="createAnother"]),
                    .fi-ac-btn-action[x-on\:click*="close"],
                    .fi-modal-footer .fi-btn:not([type="submit"]):not([class*="fi-color-danger"]):not([wire\:click*="createAnother"]) {
                        background-color: #dc2626 !important; /* Red for Cancel */
                        border-color: #dc2626 !important;
                        color: #ffffff !important;
                    }

                    /* Refresh Buttons - Specific Override */
                    .fi-btn[title*="Refresh" i],
                    .fi-btn[aria-label*="Refresh" i],
                    .fi-btn[wire\:click*="refresh" i],
                    .fi-icon-btn[title*="Refresh" i],
                    .fi-icon-btn[aria-label*="Refresh" i],
                    .fi-icon-btn[wire\:click*="refresh" i] {
                        background-color: #2f4f4f !important; /* DarkSlateGrey */
                        border-color: #2f4f4f !important;
                        color: #ffffff !important;
                    }
                    
                    .fi-btn[title*="Refresh" i]:hover,
                    .fi-icon-btn[title*="Refresh" i]:hover {
                        background-color: #3b6363 !important;
                    }

                    /* Create & Create Another Buttons - Specific SuperAdmin Style */
                    .fi-btn.fi-color-gray[wire\:click*="createAnother"],
                    .fi-ac-btn-action.fi-color-gray[wire\:click*="createAnother"],
                    button[wire\:click*="createAnother"] {
                        background-color: #ffffff !important;
                        color: #000000 !important;
                        border: 1px solid #d1d5db !important;
                    }
                    
                    .fi-btn.fi-color-gray[wire\:click*="createAnother"]:hover,
                    button[wire\:click*="createAnother"]:hover {
                        background-color: #f3f4f6 !important;
                        color: #000000 !important;
                        border: 1px solid #9ca3af !important;
                    }

                    /* Manage All Logs Button - "Pop Out" Style */
                    .tm-manage-logs-btn {
                        background-color: #2f4f4f !important;
                        color: #ffffff !important;
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
                        transition: all 0.2s ease-in-out !important;
                        font-weight: 700 !important;
                        padding: 8px 16px !important;
                        border: 1px solid #2f4f4f !important;
                    }
                    
                    .tm-manage-logs-btn:hover {
                        background-color: #3b6363 !important;
                        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2), 0 4px 6px -2px rgba(0, 0, 0, 0.1) !important;
                        transform: translateY(-2px) !important;
                    }
                    
                    .tm-manage-logs-btn span, .tm-manage-logs-btn svg {
                        color: #ffffff !important;
                        fill: #ffffff !important;
                    }

                    /* 5. Cargo Module Inspired Styling for SuperAdmin */
                    /* Global Header Styling */
                    .fi-header {
                        background-color: #2f4f4f !important;
                        padding: 25px 35px !important;
                        color: #ffffff !important;
                        border-radius: 10px !important;
                        margin-bottom: 20px !important;
                    }
                    .fi-header-heading {
                        color: #ffffff !important;
                        font-size: 24px !important;
                        font-weight: 600 !important;
                    }
                    .fi-header-subheading, .fi-header-subheading * {
                        color: rgba(255,255,255,0.85) !important;
                        font-size: 13px !important;
                    }

                    /* Table Styling */
                    .fi-ta-ctn, .fi-ta-content {
                        background: #ffffff !important;
                        border-radius: 10px !important;
                        box-shadow: 0 1px 3px rgba(0,0,0,.08) !important;
                        overflow: hidden !important;
                        border: none !important;
                    }
                    .fi-ta-header {
                        background-color: #2f4f4f !important;
                        color: #ffffff !important;
                        padding: 15px 25px !important;
                    }
                    .fi-ta-header-heading {
                        color: #ffffff !important;
                        font-size: 15px !important;
                        font-weight: 600 !important;
                    }
                    
                    /* 6. Professional Table Header Styling */
                    /* Force end-to-end background on the header row */
                    .fi-ta-content table thead,
                    .fi-ta-content table thead tr {
                        background-color: #2f4f4f !important;
                    }

                    /* Style individual header cells */
                    th.fi-ta-header-cell {
                        background-color: #2f4f4f !important;
                        padding: 12px 20px !important;
                        border: none !important;
                    }
                    
                    /* Force white color on all text, icons, and buttons inside header */
                    th.fi-ta-header-cell *,
                    th.fi-ta-header-cell span,
                    th.fi-ta-header-cell button,
                    th.fi-ta-header-cell svg {
                        color: #ffffff !important;
                        fill: #ffffff !important;
                        font-size: 10px !important;
                        font-weight: 700 !important;
                        text-transform: uppercase !important;
                        letter-spacing: .1em !important;
                    }

                    /* Remove default hover backgrounds from sortable headers */
                    th.fi-ta-header-cell:hover,
                    th.fi-ta-header-cell button:hover {
                        background-color: #3b6363 !important;
                    }

                    /* Table Body Rows */
                    .fi-ta-record, tr.fi-ta-record {
                        border-bottom: 1px solid #f3f4f6 !important;
                        transition: background-color .15s !important;
                    }
                    .fi-ta-record:hover, tr.fi-ta-record:hover {
                        background-color: #f9fafb !important;
                    }
                    .fi-ta-cell, td.fi-ta-cell {
                        padding: 16px 20px !important;
                        font-size: 13px !important;
                        color: #374151 !important;
                    }
                    
                    /* Sidebar Width Optimization */
                    :root {
                        --sidebar-width: 16rem;
                    }

                    /* ── TOPBAR HEADER SYSTEM (independent of the tenant admin panel) ──
                       This panel forces a fixed black topbar regardless of light/dark
                       mode (see rule 7 above), so the pill variables below are fixed
                       to the dark palette rather than toggling like the tenant panel. */
                    :root {
                        --tm-pill-border: rgba(255,255,255,.14);
                        --tm-pill-bg: rgba(255,255,255,.05);
                        --tm-pill-bg-hover: rgba(255,255,255,.12);
                        --tm-pill-text: #e2e8f0;
                        --tm-pill-muted: #94a3b8;
                    }

                    /* Hide the native Filament user menu — replaced by livewire:profile-menu */
                    .fi-user-menu { display: none !important; }

                    .tm-pill {
                        display: inline-flex; align-items: center; gap: 7px;
                        height: 32px; padding: 0 11px; border-radius: 8px;
                        border: 1px solid var(--tm-pill-border) !important; background: var(--tm-pill-bg) !important;
                        color: var(--tm-pill-text) !important; font-size: 12px; font-weight: 500;
                        white-space: nowrap; transition: background .15s ease; cursor: default;
                    }
                    button.tm-pill { cursor: pointer; }
                    button.tm-pill:hover { background: var(--tm-pill-bg-hover) !important; }
                    .tm-pill svg { width: 14px; height: 14px; flex-shrink: 0; }
                    .tm-pill.tm-pill svg { color: var(--tm-pill-muted) !important; }

                    .tm-clock-date { color: var(--tm-pill-muted); }
                    .tm-clock-pill, .tm-company-badge { display: none; }
                    @media (min-width: 1024px) { .tm-clock-pill, .tm-company-badge { display: inline-flex; } }
                    .tm-company-badge { max-width: 220px; }
                    .tm-company-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                    .tm-impersonating-dot { width: 7px; height: 7px; border-radius: 50%; background: #f59e0b; flex-shrink: 0; margin-left: 2px; }

                    .tm-chevron { width: 12px !important; height: 12px !important; transition: transform .15s ease; }
                    .tm-chevron-open { transform: rotate(180deg); }

                    .tm-dropdown {
                        position: absolute; top: calc(100% + 8px); right: 0;
                        background: #1e3535; border: 1px solid #243d3d; border-radius: 12px;
                        box-shadow: 0 20px 60px rgba(0,0,0,.35), 0 4px 16px rgba(0,0,0,.08);
                        z-index: 500; overflow: hidden; font-family: inherit;
                    }
                    .tm-menu-label { font-size: 10px; font-weight: 600; color: rgba(255,255,255,.45); padding: 10px 14px 6px; text-transform: uppercase; letter-spacing: .06em; }
                    .tm-menu-item {
                        display: flex; align-items: center; gap: 9px; width: 100%; text-align: left;
                        padding: 8px 14px; font-size: 12px; font-weight: 500; color: #e8f0ee !important;
                        background: none; border: none; cursor: pointer; transition: background .12s ease;
                    }
                    .tm-menu-item:hover { background: rgba(255,255,255,.06); }
                    .tm-menu-item-sel { background: rgba(78,154,154,.18); }

                    .tm-curr-symbol { opacity: .7; }
                    .tm-curr-symbol-box { width: 22px; height: 22px; border-radius: 5px; background: rgba(255,255,255,.07); display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; color: rgba(255,255,255,.7); flex-shrink: 0; }
                    .tm-check { width: 15px !important; height: 15px !important; margin-left: auto; }
                    .tm-check.tm-check { color: #4e9a9a !important; }
                    .tm-curr-dropdown { width: 190px; padding: 6px 0; }

                    .tm-profile-trigger { display: flex; align-items: center; gap: 9px; padding: 4px 10px 4px 4px; border-radius: 10px; border: none; background: none; cursor: pointer; transition: background .15s ease; }
                    .tm-profile-trigger:hover { background: var(--tm-pill-bg-hover); }
                    .tm-avatar { width: 30px; height: 30px; border-radius: 50%; background: #2f4f4f; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; border: 1.5px solid #4e9a9a; }
                    .tm-avatar-lg { width: 38px; height: 38px; font-size: 13px; }
                    .tm-avatar-img { object-fit: cover; }
                    .tm-profile-trigger-text { display: none; text-align: left; line-height: 1.2; }
                    @media (min-width: 1024px) { .tm-profile-trigger-text { display: block; } }
                    .tm-profile-name { font-size: 12px; font-weight: 600; color: var(--tm-pill-text); }
                    .tm-profile-role { font-size: 10px; color: var(--tm-pill-muted); }
                    .tm-profile-trigger .tm-chevron { color: var(--tm-pill-muted); }

                    .tm-profile-dropdown { width: 250px; }
                    .tm-pd-header { display: flex; align-items: center; gap: 10px; padding: 14px; border-bottom: 1px solid #243d3d; }
                    .tm-pd-header-text { min-width: 0; }
                    .tm-pd-name { font-size: 13px; font-weight: 600; color: #f7fafa; }
                    .tm-pd-email { font-size: 10px; color: rgba(255,255,255,.5); margin-top: 1px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                    .tm-pd-section { padding: 6px; }
                    .tm-pd-section + .tm-pd-section { border-top: 1px solid #243d3d; }
                    .tm-pd-item { display: flex; align-items: center; gap: 9px; width: 100%; text-align: left; padding: 8px 10px; border-radius: 8px; border: none; background: none; font-size: 12px; font-weight: 500; color: #e8f0ee; cursor: pointer; transition: background .12s ease; }
                    .tm-pd-item:hover { background: rgba(255,255,255,.06); }
                    .tm-pd-item svg { width: 15px; height: 15px; flex-shrink: 0; }
                    .tm-pd-item.tm-pd-item svg { color: rgba(255,255,255,.5) !important; }
                    .tm-pd-danger { color: #f87171; }
                    .tm-pd-danger.tm-pd-danger svg { color: #f87171 !important; }

                    .tm-theme-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 10px; }
                    .tm-theme-label { font-size: 12px; color: #e8f0ee; }
                    .tm-theme-toggle { display: flex; border: 1px solid #243d3d; border-radius: 6px; overflow: hidden; }
                    .tm-tog-opt { padding: 5px 8px; background: none; border: none; color: rgba(255,255,255,.5); cursor: pointer; display: flex; align-items: center; transition: all .12s ease; }
                    .tm-tog-opt svg { width: 13px; height: 13px; }
                    .tm-tog-opt.tm-tog-opt svg { color: rgba(255,255,255,.5) !important; }
                    .tm-tog-auto { font-size: 10px; padding: 5px 7px; }
                    .tm-tog-act { background: #4e9a9a; color: #fff !important; }
                    .tm-tog-act.tm-tog-act svg { color: #fff !important; }

                    .tm-dropdown.tm-dropdown button:hover svg,
                    .tm-pill.tm-pill:hover svg,
                    .tm-profile-trigger.tm-profile-trigger:hover svg {
                        color: inherit !important;
                    }
                </style>
            ')
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, function (): string {
                if (! auth()->check()) return '';

                $platformBadge = view('filament.partials.platform-badge', [
                    'companyCount' => \App\Models\Company::count(),
                ])->render();

                $clockPill = view('filament.partials.clock-pill')->render();

                $widgets = \Illuminate\Support\Facades\Blade::render('
                    <livewire:currency-switcher />
                    <livewire:notifications-dropdown />
                    <livewire:profile-menu />
                ');

                return '<div class="flex items-center gap-2">' . $platformBadge . $clockPill . $widgets . '</div>';
            })
            ->renderHook(PanelsRenderHook::BODY_END, fn (): string => \Illuminate\Support\Facades\Blade::render('<livewire:ai-panel />'))
            ->renderHook(PanelsRenderHook::FOOTER, fn (): string => '
                <div class="flex items-center justify-center p-4 text-xs text-white/50 font-medium opacity-70">
                    ' . e(\App\Models\PlatformSetting::getSetting('footer_text', '© ' . date('Y') . ' Tanaki Hub')) . '
                </div>
            ')
            ->renderHook(PanelsRenderHook::TOPBAR_END, function (): string {
                $user = auth()->user();

                if (! $user) {
                    return '';
                }

                $label = $user->totp_secret ? 'Manage TOTP' : 'Setup TOTP';

                return '
                    <form method="GET" action="' . e(route('two-factor.totp.setup')) . '" class="ms-2">
                        <button type="submit" class="fi-btn fi-color-info fi-btn-color-info fi-size-sm rounded-lg px-3 py-1.5 text-xs">
                            ' . e($label) . '
                        </button>
                    </form>
                ';
            })
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureUserSecurity::class,
                \App\Http\Middleware\EnsureTwoFactorVerified::class,
                \App\Http\Middleware\EnsureUserIsSuperAdmin::class,
            ]);
    }
}
