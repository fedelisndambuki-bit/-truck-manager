<?php

namespace App\Filament\SuperAdmin\Pages\Auth;

use App\Filament\Concerns\HasCaseInsensitiveLogin;
use Filament\Facades\Filament;
use Filament\Pages\Auth\Login as BaseAuth;

class Login extends BaseAuth
{
    use HasCaseInsensitiveLogin;

    /**
     * The 'web' and 'super_admin' guards share one Laravel session (see
     * config/auth.php) so a Super Admin can stay signed in to both panels
     * at once. But Laravel's "redirect to intended URL" is session-wide,
     * not guard-scoped: if this browser was ever bounced to the tenant
     * login while unauthenticated there, the base Login::mount() (which
     * calls redirect()->intended(...) for an already-authenticated user)
     * would replay that stale intended URL here — sending an already
     * logged-in Super Admin to the OTHER panel's login screen instead of
     * the Control Room dashboard. Discard an intended URL that doesn't
     * belong to this panel before the parent redirect logic runs.
     */
    public function mount(): void
    {
        $intendedPath = parse_url((string) session('url.intended'), PHP_URL_PATH);

        if ($intendedPath && ! str_starts_with(ltrim($intendedPath, '/'), Filament::getCurrentPanel()->getPath())) {
            session()->forget('url.intended');
        }

        parent::mount();
    }
}
