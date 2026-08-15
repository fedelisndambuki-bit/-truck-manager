<?php

namespace App\Http\Middleware;

use App\Models\PlatformSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPlatformMaintenance
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. If it's a Super Admin panel route, allow it.
        if ($request->is('super-admin*')) {
            return $next($request);
        }

        // 2. Always allow login and logout pages so Super Admins can authenticate
        if ($request->is('*/login') || $request->is('*/logout') || $request->is('livewire*')) {
            return $next($request);
        }

        // 3. Check maintenance mode
        if (PlatformSetting::getSetting('maintenance_mode', false)) {
            $user = auth()->user();
            
            // 4. Allow Super Admin to bypass on ANY route
            if ($user && $user->hasRole('Super Admin')) {
                return $next($request);
            }

            // 5. Block everyone else
            abort(503, 'The platform is currently undergoing maintenance. Please try again later.');
        }

        return $next($request);
    }
}
