<?php

namespace App\Livewire;

use App\Filament\Pages\ProfileSettings;
use App\Filament\Resources\BrandSettingResource;
use App\Filament\Resources\UserResource;
use App\Filament\SuperAdmin\Pages\ProfileSettings as SuperAdminProfileSettings;
use App\Filament\SuperAdmin\Pages\Settings as SuperAdminSettings;
use App\Models\BrandSetting;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ProfileMenu extends Component
{
    public function render()
    {
        $user = Auth::user();
        $isSuperAdmin = $user?->hasRole('Super Admin') ?? false;
        $inSuperAdminPanel = filament()->getId() === 'super-admin';

        // The super-admin panel isn't scoped to one company, so it gets its own
        // "Platform" section instead of the tenant Organisation/Team/Billing links
        // (which live in the admin panel's own Resources and shouldn't be resolved
        // from a different panel's context).
        if ($inSuperAdminPanel) {
            $companyName = 'All ' . Company::count() . ' companies';
            $canViewOrganisation = false;
            $canViewTeam = false;
            $canViewBilling = false;
            $canViewPlatformSettings = $user !== null;
            $organisationUrl = null;
            $teamUrl = null;
            $platformSettingsUrl = $canViewPlatformSettings ? SuperAdminSettings::getUrl() : null;
        } else {
            $companyName = null;
            if ($user?->company_id) {
                $brand = BrandSetting::where('company_id', $user->company_id)->first();
                $companyName = $brand?->company_name ?: $user->company?->name;
            }
            $companyName ??= $isSuperAdmin ? 'All companies' : 'Platform';
            $canViewOrganisation = $user && BrandSettingResource::canViewAny();
            $canViewTeam = $user && UserResource::canViewAny();
            $canViewBilling = $user && ! $isSuperAdmin;
            $canViewPlatformSettings = false;
            $organisationUrl = BrandSettingResource::getUrl();
            $teamUrl = UserResource::getUrl('index');
            $platformSettingsUrl = null;
        }

        return view('livewire.profile-menu', [
            'userName' => $user?->name ?? 'Guest',
            'userEmail' => $user?->email ?? '',
            'initials' => $this->initialsFor($user?->name),
            'avatarUrl' => $user?->avatar_path ? Storage::disk('public')->url($user->avatar_path) : null,
            'roleName' => $user?->getRoleNames()->first() ?? 'User',
            'companyName' => $companyName,
            'canViewOrganisation' => $canViewOrganisation,
            'canViewTeam' => $canViewTeam,
            'canViewBilling' => $canViewBilling,
            'canViewPlatformSettings' => $canViewPlatformSettings,
            'organisationUrl' => $organisationUrl,
            'teamUrl' => $teamUrl,
            'platformSettingsUrl' => $platformSettingsUrl,
            // Each panel has its own ProfileSettings page (App\Filament\Pages vs
            // App\Filament\SuperAdmin\Pages) so a Super Admin never gets routed into
            // a tenant company's admin panel — and its branding — just to edit a photo.
            'profileSettingsUrl' => $inSuperAdminPanel ? SuperAdminProfileSettings::getUrl() : ProfileSettings::getUrl(),
            // Dedicated per-guard logout route (see PanelLogoutController) instead
            // of Filament's own /logout, which invalidates the whole session and
            // would log the other guard out too now that they're independent.
            'logoutUrl' => route('panel-logout', ['guard' => $inSuperAdminPanel ? 'super_admin' : 'web']),
        ]);
    }

    private function initialsFor(?string $name): string
    {
        if (! $name) {
            return '?';
        }

        $parts = preg_split('/\s+/', trim($name));
        $initials = strtoupper(($parts[0][0] ?? '') . ($parts[1][0] ?? ''));

        return $initials ?: '?';
    }
}
