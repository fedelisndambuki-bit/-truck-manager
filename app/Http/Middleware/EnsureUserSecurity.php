<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        // Super Admin impersonation bypasses all security locks on the target account
        if ($request->session()->has('impersonator_id')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $user->hasRole('Super Admin')) {
            $company = $user->company;

            if ($company && ($company->status === 'suspended' || $company->is_active === false)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                abort(403, 'Company account is suspended.');
            }
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'Account is temporarily locked.');
        }

        if (! $request->session()->has('auth_login_at')) {
            $request->session()->put('auth_login_at', now()->toIso8601String());
        }

        if ($user->force_logout_at) {
            $sessionLoginAt = Carbon::parse($request->session()->get('auth_login_at'));

            if ($sessionLoginAt->lessThanOrEqualTo($user->force_logout_at)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/admin/login');
            }
        }

        return $next($request);
    }
}
