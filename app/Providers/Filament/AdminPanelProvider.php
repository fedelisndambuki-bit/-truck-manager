<?php

namespace App\Providers\Filament;

use App\Models\BrandSetting;
use App\Filament\Widgets\DriverLicenseAlert;
use App\Filament\Widgets\DriverSubmissionHealth;
use App\Filament\Widgets\FleetOverview;
use App\Filament\Widgets\LatestTrips;
use App\Filament\Widgets\ExpenseChart;
use App\Filament\Widgets\ProfitLossOverview;
use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\PlanUsageOverview;
use App\Http\Middleware\FilamentAuthenticate;
use App\Http\Middleware\EnsureTrialNotExpired;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Filament\Widgets;
use App\Filament\Resources\TripResource;
use App\Models\Trip;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\Facades\Blade;
use Filament\View\PanelsRenderHook;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Filament's own ->registration() flow logs the new user straight
        // in — incompatible with the access-request model, where a
        // submission is a prospect, not a tenant, until a SuperAdmin
        // approves it. Public sign-up now lives entirely outside this
        // panel at the /request-access route (see routes/web.php); the
        // 'enable_registration' PlatformSetting still gates whether that
        // route accepts new submissions.
        $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->passwordReset();

        return $panel
            ->navigationGroups([
                'Construction',
                'Safety & Incident Management',
                'Labour & HR Management',
                'Materials Management',
                'Subcontractor Management',
                'Procurement',
                'General',
                'Fleet Management',
                'Quarry',
                'Driver Submissions',
                'CRM & Sales',
                'Accounting',
                'Financials',
                'Inventory',
                'Settings',
                'User Management',
                'Account',
            ])
            ->brandName(function () {
                $user = auth()->user();
                if ($user) {
                    $brandSetting = \App\Models\BrandSetting::where('company_id', $user->company_id)->first();
                    if ($brandSetting && $brandSetting->company_name) {
                        return $brandSetting->company_name;
                    }
                    if ($user->company && $user->company->name) {
                        return $user->company->name;
                    }
                }
                return \App\Models\PlatformSetting::getSetting('tenant_default_platform_name', 'Truck Manager');
            })
            ->brandLogo(function () {
                $user = auth()->user();
                if ($user && $user->company_id) {
                    $brand = \App\Models\BrandSetting::where('company_id', $user->company_id)->first();
                    if ($brand && $brand->logo_path) {
                        $url = \Illuminate\Support\Facades\Storage::disk('public')->url($brand->logo_path);
                        return new \Illuminate\Support\HtmlString(
                            '<img src="' . e($url) . '" alt="logo" style="height:36px;max-width:180px;object-fit:contain;display:block;">'
                        );
                    }
                }
                return null;
            })
            ->favicon(asset('images/brand/tanaki-hub-icon-light.png'))
            ->colors([
                'primary' => Color::hex('#2f4f4f'),
                'danger' => '#ef4444',
                'success' => '#10b981',
                'warning' => '#f59e0b',
                'info' => '#3b82f6',
            ])
            ->font('Inter')
            ->maxContentWidth(\Filament\Support\Enums\MaxWidth::Full)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Widgets\FinancialSummaryWidget::class,
                \App\Filament\Widgets\WalletOverview::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->breadcrumbs(true)
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
                FilamentAuthenticate::class,
                \App\Http\Middleware\EnsureUserSecurity::class,
                \App\Http\Middleware\EnsureTwoFactorVerified::class,
                EnsureTrialNotExpired::class,
            ])
            ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, function (): string {
                if (! auth()->check()) return '';

                $user = auth()->user();
                $isSuperAdmin = $user->hasRole('Super Admin');

                $companyName = null;
                if ($user->company_id) {
                    $brand = \App\Models\BrandSetting::where('company_id', $user->company_id)->first();
                    $companyName = $brand?->company_name ?: $user->company?->name;
                }
                $companyName ??= $isSuperAdmin ? 'All companies' : 'Platform';

                $companyBadge = view('filament.partials.company-badge', [
                    'companyName' => $companyName,
                    'isImpersonating' => (bool) session('impersonator_id'),
                ])->render();

                $clockPill = view('filament.partials.clock-pill')->render();

                // Note: $companyBadge already contains the (escaped) company name as rendered HTML.
                // It must NOT be passed back through Blade::render() — re-compiling already-rendered
                // HTML as a Blade template would let a company name containing "@php" execute as code.
                $widgets = \Illuminate\Support\Facades\Blade::render('
                    <livewire:currency-switcher />
                    <livewire:notifications-dropdown />
                    <livewire:profile-menu />
                ');

                return '<div class="flex items-center gap-2">' . $companyBadge . $clockPill . $widgets . '</div>';
            })
            ->renderHook(PanelsRenderHook::BODY_END, fn (): string => auth()->guard('web')->check()
                ? \Illuminate\Support\Facades\Blade::render('<livewire:ai-panel />')
                : '')
            ->renderHook(PanelsRenderHook::FOOTER, function (): string {
                $user = auth()->user();
                $name = \App\Models\PlatformSetting::getSetting('tenant_default_platform_name', 'Truck Manager');
                
                if ($user) {
                    $brandSetting = \App\Models\BrandSetting::where('company_id', $user->company_id)->first();
                    if ($brandSetting && $brandSetting->company_name) {
                        $name = $brandSetting->company_name;
                    } elseif ($user->company && $user->company->name) {
                        $name = $user->company->name;
                    }
                }

                $footerText = \App\Models\PlatformSetting::getSetting('footer_text', '© ' . date('Y') . ' ' . $name);

                return '
                    <footer class="fi-footer w-full bg-[#2f4f4f] text-white mt-auto">
                        <div class="mx-auto max-w-full px-4 py-1 flex items-center justify-center text-[10px] font-medium opacity-90 uppercase tracking-widest">
                            ' . e($footerText) . '
                        </div>
                    </footer>
                ';
            })
            ->renderHook(PanelsRenderHook::BODY_START, function (): string {
                if (! auth()->check()) return '';
                $user = auth()->user();
                $html = '';

                // Impersonation banner — shown whenever a super admin is impersonating this user
                if (session('impersonator_id')) {
                    $impersonatorName = e(session('impersonator_name', 'Super Admin'));
                    $stopUrl          = route('superadmin.impersonation.stop');
                    $csrfToken        = csrf_token();
                    $userName         = e($user->name);
                    $userEmail        = e($user->email);
                    $html .= '
                    <div style="background:#92400e;color:#fff;padding:10px 20px;font-size:13px;font-weight:600;letter-spacing:.3px;position:relative;z-index:50;display:flex;align-items:center;justify-content:center;gap:16px;flex-wrap:wrap;">
                        <span>🎭 <strong>' . $impersonatorName . '</strong> is impersonating <strong>' . $userName . '</strong> (' . $userEmail . ')</span>
                        <form method="POST" action="' . $stopUrl . '" style="display:inline;margin:0;">
                            <input type="hidden" name="_token" value="' . $csrfToken . '">
                            <button type="submit" style="background:#fff;color:#92400e;border:none;padding:5px 14px;border-radius:6px;font-weight:700;cursor:pointer;font-size:12px;white-space:nowrap;">
                                End Impersonation
                            </button>
                        </form>
                    </div>';
                }

                // Trial expiry banner
                if (! $user->hasRole('Super Admin')) {
                    $company = $user->company;
                    if ($company && $company->trial_ends_at) {
                        $sub = $company->currentSubscription;
                        if (! $sub || $sub->status !== 'active') {
                            $daysLeft = (int) now()->diffInDays($company->trial_ends_at, false);
                            if ($daysLeft >= 0) {
                                $color  = $daysLeft <= 2 ? '#dc2626' : ($daysLeft <= 4 ? '#d97706' : '#2f4f4f');
                                $plural = $daysLeft === 1 ? 'day' : 'days';
                                $html .= '<div style="background:' . $color . ';color:#fff;text-align:center;padding:8px 16px;font-size:13px;font-weight:600;letter-spacing:.3px;position:relative;z-index:50;">
                                    ⏳ Your free trial expires in <strong>' . $daysLeft . ' ' . $plural . '</strong>. &nbsp;
                                    <a href="/admin/manage-subscription" style="color:#fff;text-decoration:underline;font-weight:700;">Upgrade now →</a>
                                </div>';
                            }
                        }
                    }
                }

                return $html;
            })
            ->renderHook(\Filament\View\PanelsRenderHook::HEAD_END, fn (): string => '
                <link rel="preconnect" href="https://fonts.googleapis.com">
                <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Syne:wght@700;800&display=swap" rel="stylesheet">
                <style>
                    /* ── 1. SIDEBAR ─────────────────────────────────────── */
                    .fi-sidebar { background-color: #2f4f4f !important; }

                    .fi-sidebar *:not(.fi-sidebar-item-active *) {
                        color: #e2e8f0 !important;
                        transition: color 0.15s ease !important;
                    }

                    .fi-sidebar-item:hover *,
                    .fi-sidebar-group:hover *,
                    .fi-sidebar-item-button:hover *,
                    aside.fi-sidebar a:hover *,
                    .fi-sidebar-item-active *,
                    .fi-sidebar-item-active:hover * {
                        color: #60a5fa !important;
                    }

                    .fi-sidebar-item-active {
                        background-color: rgba(255,255,255,0.1) !important;
                        border-radius: 8px !important;
                    }

                    /* Brand logo area in sidebar — overrides the login page .fi-logo rules
                       (centered flex, Playfair font, 28px margin) which are otherwise unscoped
                       in theme.css and were bleeding into the sidebar, and drops the
                       brightness(0) invert(1) filter that was rendering uploaded logos as a
                       plain white silhouette (or invisible, for logos with transparent PNGs). */
                    .fi-sidebar-header { padding: 12px 16px !important; }
                    .fi-sidebar-header .fi-logo {
                        display: flex !important;
                        align-items: center !important;
                        justify-content: flex-start !important;
                        gap: 10px !important;
                        margin-bottom: 0 !important;
                        font-family: "Inter", sans-serif !important;
                        height: auto !important;
                    }
                    .fi-sidebar-header .fi-logo img {
                        max-height: 40px !important;
                        max-width: 100% !important;
                        width: auto !important;
                        height: auto !important;
                        object-fit: contain !important;
                        border-radius: 8px !important;
                        filter: none !important;
                    }

                    /* ── 2. TOPBAR — light mode ──────────────────────────── */
                    header.fi-topbar {
                        background: #ffffff !important;
                        border-bottom: 1px solid #e2e8f0 !important;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.06) !important;
                    }
                    header.fi-topbar .fi-topbar-breadcrumbs-ctn,
                    header.fi-topbar .fi-breadcrumbs,
                    header.fi-topbar .fi-breadcrumbs * { color: #475569 !important; }

                    /* ── 2b. TOPBAR — dark mode ─────────────────────────── */
                    .dark header.fi-topbar {
                        background: #0f172a !important;
                        border-bottom: 1px solid rgba(255,255,255,0.08) !important;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.4) !important;
                    }
                    .dark header.fi-topbar .fi-topbar-breadcrumbs-ctn,
                    .dark header.fi-topbar .fi-breadcrumbs,
                    .dark header.fi-topbar .fi-breadcrumbs * { color: #94a3b8 !important; }

                    /* Icons in topbar */
                    header.fi-topbar button svg { color: #475569 !important; }
                    .dark header.fi-topbar button svg { color: #94a3b8 !important; }
                    header.fi-topbar button:hover svg { color: #2f4f4f !important; }

                    /* Avatar ring */
                    .fi-user-avatar {
                        ring-color: #2f4f4f !important;
                        box-shadow: 0 0 0 2px #2f4f4f !important;
                    }
                    .fi-avatar img {
                        object-fit: contain !important;
                        background: #f8fafc !important;
                        padding: 2px !important;
                    }

                    /* ── 3. UTILITIES ───────────────────────────────────── */
                    [x-cloak] { display: none !important; }
                    ::-webkit-scrollbar { display: none !important; }
                    * { scrollbar-width: none !important; }

                    /* ── 4. LAYOUT / FOOTER ─────────────────────────────── */
                    .fi-main-ctn { min-height: 100vh; display: flex; flex-direction: column; }
                    .fi-main { flex-grow: 1; padding-bottom: 2rem; }
                    .fi-footer { background-color: #2f4f4f !important; }

                    /* ── 5. MANAGE LOGS BUTTON ──────────────────────────── */
                    .tm-manage-logs-btn {
                        background-color: #2f4f4f !important;
                        color: #ffffff !important;
                        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06) !important;
                        transition: all 0.2s ease !important;
                        font-weight: 700 !important;
                        padding: 8px 16px !important;
                        border: 1px solid #2f4f4f !important;
                    }
                    .tm-manage-logs-btn:hover {
                        background-color: #3b6363 !important;
                        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2), 0 4px 6px -2px rgba(0,0,0,0.1) !important;
                        transform: translateY(-2px) !important;
                    }
                    .tm-manage-logs-btn span, .tm-manage-logs-btn svg {
                        color: #ffffff !important; fill: #ffffff !important;
                    }

                    /* ── 6. TOPBAR HEADER SYSTEM ─────────────────────────── */
                    :root {
                        --tm-pill-border: rgba(15,23,42,.12);
                        --tm-pill-bg: rgba(15,23,42,.03);
                        --tm-pill-bg-hover: rgba(15,23,42,.07);
                        --tm-pill-text: #1e293b;
                        --tm-pill-muted: #64748b;
                    }
                    .dark {
                        --tm-pill-border: rgba(255,255,255,.14);
                        --tm-pill-bg: rgba(255,255,255,.05);
                        --tm-pill-bg-hover: rgba(255,255,255,.12);
                        --tm-pill-text: #e2e8f0;
                        --tm-pill-muted: #94a3b8;
                    }

                    /* Hide the native Filament user menu — replaced by livewire:profile-menu */
                    .fi-user-menu { display: none !important; }

                    /* Inline topbar pill (clock, company badge, currency trigger) */
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
                    /* .tm-pill.tm-pill doubles the class on purpose to out-specificity the generic
                       `header.fi-topbar button svg` rule above, which otherwise wins on element count. */
                    .tm-pill.tm-pill svg { color: var(--tm-pill-muted) !important; }

                    .tm-clock-date { color: var(--tm-pill-muted); }
                    .tm-clock-pill, .tm-company-badge { display: none; }
                    @media (min-width: 1024px) { .tm-clock-pill, .tm-company-badge { display: inline-flex; } }
                    .tm-company-badge { max-width: 180px; }
                    .tm-company-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                    .tm-impersonating-dot { width: 7px; height: 7px; border-radius: 50%; background: #f59e0b; flex-shrink: 0; margin-left: 2px; }

                    .tm-chevron { width: 12px !important; height: 12px !important; transition: transform .15s ease; }
                    .tm-chevron-open { transform: rotate(180deg); }

                    /* Shared dark floating dropdown surface (currency / profile popovers) */
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

                    /* Profile trigger + dropdown */
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

                    /* Neutralise the generic topbar `button:hover svg { color: #2563eb }` rule above
                       (higher specificity via :hover) inside our own dropdowns/pills, so icons keep
                       their own colour on hover instead of flashing blue. */
                    .tm-dropdown.tm-dropdown button:hover svg,
                    .tm-pill.tm-pill:hover svg,
                    .tm-profile-trigger.tm-profile-trigger:hover svg {
                        color: inherit !important;
                    }
                </style>
            ');
    }

    public function boot(): void
    {
        $default = \App\Models\PlatformSetting::getSetting('default_currency', 'KES');
        $currency = session('currency', $default);
        \Filament\Tables\Table::$defaultCurrency = $currency;
        \Filament\Infolists\Infolist::$defaultCurrency = $currency;

        FilamentAsset::register([
            Css::make('admin-modern-ui', asset('css/admin-modern-ui.css')),
        ]);
    }

    private function getBrandSettings(): ?BrandSetting
    {
        $user = auth()->user();
        if (!$user) return BrandSetting::query()->first();
        if ($user->hasRole('Super Admin')) return BrandSetting::query()->first();
        return BrandSetting::query()->where('company_id', $user->company_id)->first();
    }
}
