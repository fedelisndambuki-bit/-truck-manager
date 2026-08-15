<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        // Explicit guard: the super-admin panel authenticates under its own
        // 'super_admin' guard (see config/auth.php), independent of the 'web'
        // guard used by tenant company accounts.
        $user = $request->user('super_admin');

        if (! $user) {
            return $next($request);
        }

        if (! $user->hasRole('Super Admin')) {
            abort(403, 'Super Admin access required.');
        }

        return $next($request);
    }
}
