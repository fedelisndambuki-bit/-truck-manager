<?php

namespace App\Http\Controllers\Auth;

use App\Enums\CompanyIndustry;
use App\Http\Controllers\Controller;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Throwable;

/**
 * Enterprise SSO for the tenant admin panel's login page (see
 * resources/views/filament/pages/auth/tanaki-login.blade.php). Deliberately
 * does NOT auto-register new accounts or companies — SSO only signs in an
 * email address that already has a User row, matching how tenants are
 * provisioned everywhere else in this app (invitation/admin-created, never
 * self-serve via a third-party identity token).
 */
class SocialiteLoginController extends Controller
{
    /**
     * Providers this endpoint will ever drive. Kept in sync with the
     * whereIn() constraint on the routes and with the config keys checked
     * by tanaki-login.blade.php to decide whether to render each button.
     */
    private const SUPPORTED_PROVIDERS = ['google', 'microsoft'];

    public function redirect(string $provider): RedirectResponse
    {
        if (! $this->providerConfigured($provider)) {
            abort(404);
        }

        $driver = Socialite::driver($provider);

        if ($provider === 'microsoft') {
            $driver->with(['tenant' => config('services.microsoft.tenant', 'common')]);
        }

        return $driver->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        if (! $this->providerConfigured($provider)) {
            abort(404);
        }

        try {
            $driver = Socialite::driver($provider);

            if ($provider === 'microsoft') {
                $driver->with(['tenant' => config('services.microsoft.tenant', 'common')]);
            }

            $oauthUser = $driver->user();
        } catch (InvalidStateException $e) {
            Log::warning("SSO callback rejected — invalid/expired state for provider [{$provider}].", [
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title('Sign-in session expired')
                ->body('Please try again.')
                ->send();

            return redirect('/admin/login');
        } catch (Throwable $e) {
            Log::warning("SSO callback failed for provider [{$provider}].", [
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->danger()
                ->title('Sign-in failed')
                ->body('We could not complete sign-in with '.ucfirst($provider).'. Please try again or use your password.')
                ->send();

            return redirect('/admin/login');
        }

        $user = $this->resolveExistingUser($provider, $oauthUser);

        if (! $user) {
            Notification::make()
                ->danger()
                ->title('No account found')
                ->body('No account was found for '.$oauthUser->getEmail().'. Contact your administrator to get access.')
                ->send();

            return redirect('/admin/login');
        }

        if (! $user->canAccessPanel(Filament::getPanel('admin'))) {
            Notification::make()
                ->danger()
                ->title('Access denied')
                ->body('Your account does not have access to this panel.')
                ->send();

            return redirect('/admin/login');
        }

        $this->linkProviderIfNeeded($user, $provider, $oauthUser);

        Auth::guard('web')->login($user, remember: true);
        request()->session()->regenerate();

        return redirect($this->redirectPathFor($user));
    }

    private function providerConfigured(string $provider): bool
    {
        if (! in_array($provider, self::SUPPORTED_PROVIDERS, true)) {
            return false;
        }

        return filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"));
    }

    private function resolveExistingUser(string $provider, SocialiteUser $oauthUser): ?User
    {
        $user = User::where('provider', $provider)
            ->where('provider_id', $oauthUser->getId())
            ->first();

        if ($user) {
            return $user;
        }

        if (! $oauthUser->getEmail()) {
            return null;
        }

        return User::where('email', $oauthUser->getEmail())->first();
    }

    private function linkProviderIfNeeded(User $user, string $provider, SocialiteUser $oauthUser): void
    {
        if ($user->provider === $provider && $user->provider_id === $oauthUser->getId()) {
            return;
        }

        $user->forceFill([
            'provider' => $provider,
            'provider_id' => $oauthUser->getId(),
        ])->save();
    }

    /**
     * Mirrors (deliberately duplicated, not refactored into a shared
     * method) the industry-based redirect in
     * App\Filament\Pages\Auth\Login::redirectTo() — that method only runs
     * inside Filament's own password-login flow, so this SSO path needs
     * its own copy to land users on the same dashboard either way.
     */
    private function redirectPathFor(User $user): string
    {
        if ($user->company?->industry === CompanyIndustry::Construction->value) {
            return route('filament.admin.pages.construction-dashboard');
        }

        if ($user->company?->industry === CompanyIndustry::Quarry->value) {
            return route('filament.admin.pages.executive-dashboard');
        }

        return route('filament.admin.pages.landing-page');
    }
}
