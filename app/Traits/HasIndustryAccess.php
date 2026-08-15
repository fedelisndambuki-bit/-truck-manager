<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait HasIndustryAccess
{
    /**
     * Override in the resource/page to restrict it to specific industries.
     * Null (the default) means visible to every industry.
     *
     * @return array<string>|null
     */
    public static function getRequiredIndustries(): ?array
    {
        return null;
    }

    public static function passesIndustryGate(): bool
    {
        $required = static::getRequiredIndustries();

        if (empty($required)) {
            return true;
        }

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        $industry = $user->company?->industry;

        // Multi-Operation companies span every industry, so they pass every
        // industry gate rather than being restricted to one required list.
        if ($industry === \App\Enums\CompanyIndustry::MultiOperation->value) {
            return true;
        }

        return $industry !== null && in_array($industry, $required, true);
    }

    /**
     * Default gate for Filament Pages using this trait directly (Resources
     * get their own canViewAny()-based gate from HasModuleAccess instead).
     * Blocks direct navigation to the page's route for the wrong industry.
     */
    public static function canAccess(): bool
    {
        return static::passesIndustryGate();
    }

    /**
     * Filament's base Page::shouldRegisterNavigation() is NOT tied to
     * canAccess() by default, so without this override a page gated by
     * canAccess() would still show its nav item to every industry — only
     * blocking the route once clicked. Tie the two together here.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::passesIndustryGate();
    }
}
