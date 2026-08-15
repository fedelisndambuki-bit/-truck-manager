<?php

namespace App\Filament\Concerns;

trait HasCaseInsensitiveLogin
{
    /**
     * SQLite (used in local/dev) compares strings case-sensitively by default,
     * so an email typed/autocapitalized with different casing than what's
     * stored would fail Auth::attempt() with a generic "credentials do not
     * match" error even when the password is correct. Normalise to lowercase
     * here to match how the User model now stores emails.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'],
        ];
    }
}
