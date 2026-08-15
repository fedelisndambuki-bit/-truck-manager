<?php

namespace App\IntegrationEngine\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Tanaki\IntegrationContracts\Enum\DeviceCategory;

/**
 * tanaki/integration-contracts is deliberately framework-free — it cannot
 * implement Laravel's Castable contract itself (see the package's UPGRADE.md
 * and CHANGELOG.md for the extraction note). This is the app-side adapter
 * that keeps IntegratedDevice's Eloquent casting working: reference this
 * class in $casts instead of DeviceCategory::class directly.
 */
class DeviceCategoryCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?DeviceCategory
    {
        return $value === null ? null : DeviceCategory::from($value);
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof DeviceCategory ? $value->value() : (string) $value;
    }
}
