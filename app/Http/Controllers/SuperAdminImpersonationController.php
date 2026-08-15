<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminImpersonationController extends Controller
{
    public function start(Request $request, User $user): RedirectResponse
    {
        // The super-admin panel authenticates under its own guard (see
        // config/auth.php), independent of the tenant 'web' guard — so the
        // acting Super Admin must be resolved from 'super_admin' explicitly.
        $actor = $request->user('super_admin');

        if (! $actor || ! $actor->hasRole('Super Admin')) {
            abort(403, 'Super Admin access required.');
        }

        if ($user->hasRole('Super Admin')) {
            abort(422, 'Cannot impersonate another Super Admin user.');
        }

        // Store who is impersonating, for the "X is impersonating Y" banner.
        // The Super Admin stays logged in under 'super_admin' throughout —
        // impersonation only logs the target user into the separate 'web'
        // guard, so no manual session/password-hash juggling between guards
        // is needed (each guard's own AuthenticateSession check now runs
        // against its own guard's session state automatically).
        $request->session()->put('impersonator_id', $actor->id);
        $request->session()->put('impersonator_name', $actor->name);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put('auth_login_at', now()->toIso8601String());

        SystemLog::create([
            'company_id' => $user->company_id,
            'action'     => 'admin_impersonation_started',
            'severity'   => 'warning',
            'description' => sprintf(
                'Super Admin %s impersonated user %s (%s).',
                $actor->email, $user->email, $user->id
            ),
        ]);

        return redirect('/admin');
    }

    /**
     * GET handler used by the Filament table action.
     * Auth::login() inside a Livewire AJAX action does not reliably propagate
     * the new session cookie to the browser; routing through a plain HTTP GET
     * request (redirect from Livewire → GET → real 302 to /admin) solves this.
     */
    public function startGet(Request $request, User $user): RedirectResponse
    {
        return $this->start($request, $user);
    }

    public function stop(Request $request): RedirectResponse
    {
        $impersonatorId   = $request->session()->pull('impersonator_id');
        $impersonatorName = $request->session()->pull('impersonator_name');

        if (! $impersonatorId) {
            return redirect('/admin');
        }

        $current = $request->user('web');

        // The Super Admin was never logged out of 'super_admin' during
        // impersonation, so ending it is just logging out of the 'web' guard
        // that was hosting the impersonated tenant user.
        Auth::guard('web')->logout();
        $request->session()->regenerate();

        SystemLog::create([
            'company_id' => $current?->company_id,
            'action'     => 'admin_impersonation_stopped',
            'severity'   => 'info',
            'description' => sprintf(
                'Super Admin %s ended impersonation session.',
                $impersonatorName ?: $impersonatorId
            ),
        ]);

        return redirect('/super-admin');
    }
}
