<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\Response;

class HandleCurrency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $default = \App\Models\PlatformSetting::getSetting('default_currency', 'KES');
        $currency = Session::get('currency', $default);
        
        // Update Laravel's Number helper default currency
        Number::useCurrency($currency);

        return $next($request);
    }
}
