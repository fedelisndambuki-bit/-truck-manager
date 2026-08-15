<?php

namespace App\Filament\Pages\Auth;

use App\Enums\CompanyIndustry;
use App\Filament\Concerns\HasCaseInsensitiveLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\Component;
use Filament\Pages\Auth\Login as BaseAuth;
use Illuminate\Support\Facades\Auth;

class Login extends BaseAuth
{
    use HasCaseInsensitiveLogin;

    /**
     * The 'web' and 'super_admin' guards share one Laravel session (see
     * config/auth.php) so a Super Admin can stay signed in to both panels
     * at once. But Laravel's "redirect to intended URL" is session-wide,
     * not guard-scoped: if this browser was ever bounced to the super-admin
     * login while unauthenticated there, the base Login::mount() (which
     * calls redirect()->intended(...) for an already-authenticated user)
     * would replay that stale intended URL here — sending an already
     * logged-in tenant user to the OTHER panel's login screen instead of
     * their own dashboard. Discard an intended URL that doesn't belong to
     * this panel before the parent redirect logic runs.
     */
    public function mount(): void
    {
        $intendedPath = parse_url((string) session('url.intended'), PHP_URL_PATH);

        if ($intendedPath && ! str_starts_with(ltrim($intendedPath, '/'), Filament::getCurrentPanel()->getPath())) {
            session()->forget('url.intended');
        }

        parent::mount();
    }

    /**
     * The premium split-screen authentication experience — see
     * resources/views/filament/pages/auth/tanaki-login.blade.php. Scoped to
     * this (tenant-facing) panel only; the super-admin panel's Login page
     * is untouched and keeps rendering Filament's stock simple view.
     */
    protected static string $view = 'filament.pages.auth.tanaki-login';

    /**
     * The stock 'layout.simple' wraps the page view in a centered,
     * max-width-constrained white card — appropriate for a traditional
     * single-panel login, but incompatible with a full-bleed 55/45
     * split-screen layout. 'layout.base' gives just the bare <html>/<body>
     * document (still with Livewire/Alpine/Filament assets loaded), and
     * the Blade view above builds 100% of the visible page itself.
     */
    protected static string $layout = 'filament-panels::components.layout.base';

    protected function redirectTo(): string
    {
        // Note: the super-admin panel now authenticates under its own
        // 'super_admin' guard (see config/auth.php), separate from this
        // panel's 'web' guard. Logging in here — even as a Super Admin —
        // no longer also authenticates them there, so we no longer redirect
        // to the super-admin dashboard; use /super-admin/login for that.

        // The generic "Fleet Landing Page" is trucking-only (see
        // App\Filament\Pages\LandingPage::getRequiredIndustries()), so
        // construction companies get routed to their own dashboard instead
        // of a page they no longer have access to.
        if (Auth::user()?->company?->industry === CompanyIndustry::Construction->value) {
            return route('filament.admin.pages.construction-dashboard');
        }

        // Quarry companies get their own Executive Dashboard command center
        // (see App\Filament\Pages\Quarry\ExecutiveDashboard) instead of the
        // generic Fleet Landing Page, which is trucking-only.
        if (Auth::user()?->company?->industry === CompanyIndustry::Quarry->value) {
            return route('filament.admin.pages.executive-dashboard');
        }

        return route('filament.admin.pages.landing-page');
    }

    /**
     * Adds the envelope prefix icon from the approved reference design,
     * on top of the parent's existing email/autocomplete/autofocus/tabindex
     * configuration — validation and behavior are untouched.
     */
    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->label('Email Address')
            ->prefixIcon('heroicon-o-envelope');
    }

    /**
     * Adds the lock prefix icon from the reference design. The reveal
     * (show/hide) eye button is already provided natively by Filament via
     * ->revealable() on the parent component — no custom JS needed for it.
     */
    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->prefixIcon('heroicon-o-lock-closed');
    }
}
