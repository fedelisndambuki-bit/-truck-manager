<?php

namespace Tanaki\IntegrationContracts\Enum;

/**
 * A closed, native enum deliberately: device lifecycle state is not
 * something a third-party driver package needs to extend — every device,
 * regardless of vendor, is always active/inactive/offline/faulted. Contrast
 * with DeviceCategory/ProtocolType, which are open value objects for exactly
 * the opposite reason.
 *
 * No Filament\Support\Contracts\HasLabel here — a hardware manufacturer
 * building a driver has no reason to have Filament installed. label()
 * presentation for Filament UIs is wired up explicitly at the point of use
 * in the application layer instead of via interface auto-detection.
 */
enum DeviceStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Offline = 'offline';
    case Faulted = 'faulted';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Offline => 'Offline',
            self::Faulted => 'Faulted',
        };
    }
}
