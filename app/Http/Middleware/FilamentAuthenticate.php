<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as BaseAuthenticate;

class FilamentAuthenticate extends BaseAuthenticate
{
    protected function redirectTo($request): string
    {
        // Send unauthenticated users to the landing page instead of /admin/login
        return '/';
    }
}
