<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Scaffolded, inactive by default (see config/services.php 'turnstile').
 * Shaped for Cloudflare Turnstile: set TURNSTILE_SITE_KEY / TURNSTILE_SECRET_KEY
 * in .env and flip 'services.turnstile.enabled' to true to go live — no
 * code changes needed once real keys exist.
 */
class CaptchaVerificationService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.turnstile.enabled', false);
    }

    public function verify(?string $token): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        if (! $token) {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => config('services.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => request()?->ip(),
            ])->json();

            return (bool) ($response['success'] ?? false);
        } catch (\Exception $e) {
            Log::error('Turnstile verification request failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
