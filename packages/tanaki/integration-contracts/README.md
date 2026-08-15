# tanaki/integration-contracts

Framework-agnostic interfaces, DTOs, enums, events, attributes, and
exceptions for building Tanaki Hub hardware integration drivers.

This package has **zero dependency on Laravel, Filament, or any other
framework** — only PHP 8.2+ and [nesbot/carbon](https://carbon.nesbot.com/).
A driver author can implement against it without installing anything Tanaki
Hub itself uses to run.

## What this package is

`tanaki/integration-contracts` is the pure-PHP boundary between Tanaki Hub
and any hardware driver — first-party or third-party. It defines:

- **Contracts** (`Tanaki\IntegrationContracts\Contract\*`) — the interfaces a
  driver implements: `DeviceDriverInterface` and its three segregated pieces
  (`HealthCheckableInterface`, `DiagnosticsInterface`,
  `DriverMetadataInterface`), `ProtocolAdapterInterface`, and 9
  category-specific interfaces under `Contract\Category\*`
  (`WeighbridgeDriverInterface`, `GpsTrackerDriverInterface`, etc).
- **DTOs** (`Tanaki\IntegrationContracts\DTO\*`) — the standardized,
  immutable value objects a driver returns. `final readonly class`, one per
  device category, plus `DeviceHealthDTO`, `DeviceDiagnosticsDTO`, and
  `DriverMetadataDTO`.
- **Enums** (`Tanaki\IntegrationContracts\Enum\*`) — `DeviceCategory` and
  `ProtocolType` are open taxonomies (see below), `DeviceStatus` and
  `ConnectionMedium` are closed native PHP enums.
- **Events** (`Tanaki\IntegrationContracts\Event\*`) — plain PHP value
  objects a driver's host application dispatches when a driver produces
  data or changes lifecycle state.
- **Attribute** (`Tanaki\IntegrationContracts\Attribute\IntegrationDriver`) —
  tags a driver class for auto-discovery.
- **Exceptions** (`Tanaki\IntegrationContracts\Exception\*`) — every one
  implements `IntegrationExceptionInterface`, so consuming code can catch
  any failure from this ecosystem with one `catch` clause.

## Installation

This package is distributed privately via a Composer path repository — it
is **not** published to Packagist. Consuming applications add:

```json
{
    "repositories": [
        { "type": "path", "url": "packages/tanaki/integration-contracts" }
    ],
    "require": {
        "tanaki/integration-contracts": "^1.0"
    }
}
```

## Why DeviceCategory and ProtocolType aren't native enums

Native PHP enums cannot be extended by third-party code. A manufacturer
building a driver for a device category this package has never heard of —
a thermal camera, say — needs a legal value to return from
`getCategory(): DeviceCategory`. Both classes trade native `match`
exhaustiveness for genuine openness:

```php
use Tanaki\IntegrationContracts\Enum\DeviceCategory;

// A built-in category:
$category = DeviceCategory::from(DeviceCategory::WEIGHBRIDGE);

// A category this package doesn't know about yet — no release required:
$category = DeviceCategory::custom('thermal_camera', 'Thermal Camera');

$category->value();   // 'thermal_camera'
$category->label();   // 'Thermal Camera'
```

Built-in values are exposed as string **class constants**
(`DeviceCategory::WEIGHBRIDGE`), not zero-argument static methods, so they
remain usable inside `#[IntegrationDriver(category: DeviceCategory::WEIGHBRIDGE)]`
— PHP attribute arguments must be constant expressions, and a class-constant
fetch qualifies where a method call would not.

## Building a driver against this package

```php
use Tanaki\IntegrationContracts\Attribute\IntegrationDriver;
use Tanaki\IntegrationContracts\Contract\Category\WeighbridgeDriverInterface;
use Tanaki\IntegrationContracts\DTO\WeighbridgeReadingDTO;
use Tanaki\IntegrationContracts\Enum\DeviceCategory;

#[IntegrationDriver(
    vendor: 'acme',
    category: DeviceCategory::WEIGHBRIDGE,
    version: '1.0.0',
    capabilities: ['read', 'health_check'],
)]
class AcmeWeighbridgeDriver implements WeighbridgeDriverInterface
{
    public function parseReading(array $rawPayload): WeighbridgeReadingDTO
    {
        // Adapt Acme's native payload shape into the standardized DTO.
    }

    // ...plus the lifecycle/health/diagnostics/metadata methods required
    // by DeviceDriverInterface — see the interface docblocks for the full
    // contract, or Tanaki Hub's driver-authoring guide for a worked example.
}
```

A driver never depends on the consuming application. It depends only on
this package (and, if it needs one, a protocol-adapter package).

## Stability

This package follows semantic versioning. See [CHANGELOG.md](CHANGELOG.md)
for release history and [UPGRADE.md](UPGRADE.md) for breaking-change
migration notes between major versions. `DeviceCategory` and `ProtocolType`
ship marked `@experimental` for the 1.x line — see the Public API Review for
the full stability classification of every type in this package.

## Testing

```bash
composer install
composer test       # PHPUnit — this package's own isolated suite, no Laravel required
composer analyse     # PHPStan static analysis
```

## License

Proprietary — see [LICENSE](LICENSE).
