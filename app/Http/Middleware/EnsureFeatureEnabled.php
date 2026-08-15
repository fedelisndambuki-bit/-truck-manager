<?php

namespace App\Http\Middleware;

use App\Services\FeatureFlagService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function __construct(private readonly FeatureFlagService $flags)
    {
    }

    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();

        if (! $this->flags->enabledForUser($featureKey, $user)) {
            abort(403, 'This feature is disabled for your company plan.');
        }

        return $next($request);
    }
}
