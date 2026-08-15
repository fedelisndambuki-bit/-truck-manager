# Changelog

All notable changes to `tanaki/integration-contracts` are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/);
versioning follows [Semantic Versioning](https://semver.org/).

## [1.0.0] — Extraction release

Extracted from Tanaki Hub's `App\IntegrationEngine\{Contracts,DTOs,Enums,
Events,Attributes,Exceptions}` into a standalone Composer package. No
application behavior changed as part of the extraction itself — every type
below already existed in the host application and passed a dedicated Public
API Review and SDK Stabilization pass before this release.

### Added

- 7 core contracts: `DeviceDriverInterface`, `DeviceRegistryInterface`,
  `DiagnosticsInterface`, `DriverFactoryInterface`, `DriverMetadataInterface`,
  `HealthCheckableInterface`, `ProtocolAdapterInterface`.
- 9 category contracts under `Contract\Category\*`.
- 12 DTOs, all `final readonly`, all `Carbon\CarbonImmutable` for timestamps.
- 4 enums: `DeviceCategory` and `ProtocolType` as open, extensible value
  objects; `DeviceStatus` and `ConnectionMedium` as closed native enums.
- 12 event value objects, framework-free (no Laravel event traits).
- `#[IntegrationDriver]` attribute for auto-discovery, all properties
  `readonly`.
- 5 exception types, all implementing the new `IntegrationExceptionInterface`
  marker.

### Notes

- `DeviceCategory` and `ProtocolType` are marked `@experimental` for the 1.x
  line — see the Public API Review's stability classification.
- This package does not implement Laravel's `Castable` contract (dropped
  during extraction to keep the package genuinely framework-free). Consuming
  applications that persist these values via Eloquent provide their own
  `CastsAttributes` adapter — see Tanaki Hub's
  `App\IntegrationEngine\Casts\{DeviceCategoryCast,ProtocolTypeCast}` for the
  reference implementation.
