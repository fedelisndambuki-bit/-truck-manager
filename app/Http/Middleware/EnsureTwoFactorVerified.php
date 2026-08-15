<?php

namespace App\Http\Middleware;

use App\Http\Controllers\TwoFactorController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        // Super Admin impersonation bypasses 2FA for the target user
        if ($request->session()->has('impersonator_id')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || ! $user->two_factor_enabled) {
            return $next($request);
        }

        if ((int) $request->session()->get('two_factor_user_id', 0) !== (int) $user->id) {
            $request->session()->forget(['two_factor_passed', 'two_factor_code_sent_at']);
        }

        if ($request->session()->get('two_factor_passed') === true) {
            return $next($request);
        }

        if ($request->routeIs('two-factor.*')) {
            return $next($request);
        }

        if (($user->two_factor_method ?: 'email') === 'email') {
            if (! $request->session()->has('two_factor_code_sent_at')) {
                TwoFactorController::issueCode($user->id, $user->email);
                $request->session()->put('two_factor_code_sent_at', now()->toIso8601String());
            }
        }

        return redirect()->route('two-factor.challenge');
    }
}
