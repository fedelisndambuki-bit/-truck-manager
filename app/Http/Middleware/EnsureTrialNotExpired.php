<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrialNotExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        // Always allow access to the subscription management page
        if ($request->is('admin/manage-subscription')) {
            return $next($request);
        }

        // Super Admin impersonation bypasses trial expiry checks
        if ($request->session()->has('impersonator_id')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user || $user->hasRole('Super Admin')) {
            return $next($request);
        }

        $company = $user->company;

        if (! $company) {
            return $next($request);
        }

        // Active paid subscription → always allow
        $sub = $company->currentSubscription;
        if ($sub && $sub->isActive()) {
            return $next($request);
        }

        // Subscription row exists but has lapsed (expires_at in the past) —
        // reflect that in status so reporting doesn't keep showing 'active'.
        if ($sub && $sub->status === 'active' && ! $sub->isActive()) {
            $sub->update(['status' => 'expired']);
            $company->update(['status' => 'expired']);

            return redirect('/admin/manage-subscription')
                ->with('error', 'Your subscription has expired. Please renew to continue.');
        }

        // Trial period → check expiry
        if ($company->trial_ends_at) {
            if ($company->trial_ends_at->isFuture()) {
                return $next($request);
            }

            // Trial has expired → reflect that in status (was previously left
            // as 'trial' forever, skewing admin reporting) and send to the
            // subscription page instead of logging out.
            if ($company->status === 'trial') {
                $company->update(['status' => 'expired']);
            }

            return redirect('/admin/manage-subscription')
                ->with('error', 'Your free trial has expired. Please subscribe to continue.');
        }

        // No trial set and no active subscription → redirect to subscription page
        return redirect('/admin/manage-subscription')
            ->with('error', 'No active subscription found. Please choose a plan to continue.');
    }
}
