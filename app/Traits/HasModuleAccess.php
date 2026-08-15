<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait HasModuleAccess
{
    use HasIndustryAccess;

    public static function canViewAny(): bool
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super Admins see everything
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Industry gate: a resource restricted to specific industries (via
        // getRequiredIndustries()) is hidden entirely from companies in a
        // different industry, independent of permissions (Spatie roles are
        // global/unscoped in this app, so they can't safely carry industry
        // separation — see app/Traits/HasIndustryAccess.php).
        if (!static::passesIndustryGate()) {
            return false;
        }

        // Get the module slug defined in the resource
        $moduleSlug = static::getModuleSlug();

        if (!$moduleSlug) {
            return true; // No module gating if not defined
        }

        // Check if user has the specific permission for this module
        return $user->can($moduleSlug);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    /**
     * HasIndustryAccess's own canAccess() only checks the industry gate
     * (deliberately, for Pages that use it directly) — Resources need the
     * full permission check too, or direct-URL navigation bypasses the
     * module-slug gate entirely even though the nav link is correctly
     * hidden. This is what closes that gap.
     */
    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    /**
     * Override this in the resource to define its module slug.
     */
    public static function getModuleSlug(): ?string
    {
        return null;
    }
}
