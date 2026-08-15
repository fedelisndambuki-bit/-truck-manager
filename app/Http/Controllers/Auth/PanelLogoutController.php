<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PanelLogoutController extends Controller
{
    /**
     * Logs out of a single named guard without touching the shared session's
     * other guards. Filament's own /logout route calls session()->invalidate(),
     * which flushes the *entire* session — since the 'web' and 'super_admin'
     * guards' login state lives in that same session, that would silently log
     * a Super Admin out of the super-admin panel just by signing out of a
     * tenant company account (or vice versa). session()->regenerate() rotates
     * the session ID/CSRF token for security without discarding the other
     * guard's data.
     */
    public function __invoke(Request $request, string $guard): RedirectResponse
    {
        Auth::guard($guard)->logout();
        $request->session()->regenerate();

        return redirect($guard === 'super_admin' ? '/super-admin/login' : '/admin/login');
    }
}
