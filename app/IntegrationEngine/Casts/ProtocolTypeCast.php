<?php

namespace App\IntegrationEngine\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Tanaki\IntegrationContracts\Enum\ProtocolType;

/**
 * See DeviceCategoryCast for why this adapter exists — the package version
 * of ProtocolType is deliberately framework-free.
 */
class ProtocolTypeCast implements CastsAttributes
{
    public function get($model, string $key, $value, array $attributes): ?ProtocolType
    {
        return $value === null ? null : ProtocolType::from($value);
    }

    public function set($model, string $key, $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof ProtocolType ? $value->value() : (string) $value;
    }
}
