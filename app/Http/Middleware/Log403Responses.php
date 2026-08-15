<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Log403Responses
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (HttpException $e) {
            if ($e->getStatusCode() === 403) {
                Log::channel('single')->warning('403 ABORT CAUGHT', [
                    'url'     => $request->fullUrl(),
                    'method'  => $request->method(),
                    'user_id' => auth()->id(),
                    'referer' => $request->header('Referer'),
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'trace'   => collect($e->getTrace())->take(8)->map(fn($f) => ($f['file'] ?? '') . ':' . ($f['line'] ?? '') . ' → ' . ($f['function'] ?? ''))->values()->toArray(),
                ]);
            }
            throw $e;
        }

        if ($response->getStatusCode() === 403) {
            Log::channel('single')->warning('403 RESPONSE CAPTURED', [
                'url'     => $request->fullUrl(),
                'method'  => $request->method(),
                'user_id' => auth()->id(),
                'referer' => $request->header('Referer'),
            ]);
        }

        return $response;
    }
}
