# Upgrade Guide

## Upgrading to 1.0.0 from the pre-extraction `App\IntegrationEngine\*` namespace

Prior to this package's existence, these types lived directly inside Tanaki
Hub under `App\IntegrationEngine\{Contracts,DTOs,Enums,Events,Attributes,
Exceptions}`. If you have code (a local driver, a listener, a test) written
against those namespaces, update your imports per this table. Nothing about
any type's public shape changed as part of this move — only its namespace
and, for `DeviceCategory`/`ProtocolType`, its Eloquent-casting mechanism
(see below).

| Old namespace | New namespace |
|---|---|
| `App\IntegrationEngine\Contracts\Categories\*` | `Tanaki\IntegrationContracts\Contract\Category\*` |
| `App\IntegrationEngine\Contracts\*` | `Tanaki\IntegrationContracts\Contract\*` |
| `App\IntegrationEngine\DTOs\*` | `Tanaki\IntegrationContracts\DTO\*` |
| `App\IntegrationEngine\Enums\*` | `Tanaki\IntegrationContracts\Enum\*` |
| `App\IntegrationEngine\Events\*` | `Tanaki\IntegrationContracts\Event\*` |
| `App\IntegrationEngine\Attributes\*` | `Tanaki\IntegrationContracts\Attribute\*` |
| `App\IntegrationEngine\Exceptions\*` | `Tanaki\IntegrationContracts\Exception\*` |

### If you cast `DeviceCategory` or `ProtocolType` on an Eloquent model

Prior to extraction, both classes implemented Laravel's `Castable` contract
directly, so `'category' => DeviceCategory::class` worked unchanged in a
model's `$casts` array. This package is framework-free and no longer
implements `Castable`. If you need this on your own models, implement a
small adapter:

```php
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Tanaki\IntegrationContracts\Enum\DeviceCategory;

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
```

Then reference the cast class instead of the enum class directly:
`'category' => DeviceCategoryCast::class`. See Tanaki Hub's own
`App\IntegrationEngine\Casts\DeviceCategoryCast` for the reference
implementation this guide is based on.

## Future major versions

No 2.0 has been released. When one is, this section will document every
breaking change and the concrete steps to migrate, per the deprecation and
compatibility policy established in the Public API Review.
