<?php

namespace App\IntegrationEngine\Registry;

use Tanaki\IntegrationContracts\Contract\DeviceRegistryInterface;
use Tanaki\IntegrationContracts\Enum\DeviceCategory;
use Tanaki\IntegrationContracts\Exception\DriverNotFoundException;

/**
 * Runtime, in-memory record of which driver classes exist for which vendor
 * keys — populated at boot by IntegrationEngineServiceProvider from the
 * DriverDiscoveryService cache. Deliberately does NOT bind any category
 * interface to one concrete class in the container: a company can run
 * multiple vendors in the same category simultaneously, so "which driver"
 * is always a per-vendor-key runtime lookup, never a compile-time binding.
 */
class DeviceRegistry implements DeviceRegistryInterface
{
    /** @var array<string, array{class: class-string, category: DeviceCategory}> */
    protected array $drivers = [];

    public function register(string $vendorKey, string $driverClass, DeviceCategory $category): void
    {
        $this->drivers[$vendorKey] = [
            'class' => $driverClass,
            'category' => $category,
        ];
    }

    public function isRegistered(string $vendorKey): bool
    {
        return isset($this->drivers[$vendorKey]);
    }

    public function driverClassFor(string $vendorKey): string
    {
        if (! $this->isRegistered($vendorKey)) {
            throw DriverNotFoundException::forVendor($vendorKey);
        }

        return $this->drivers[$vendorKey]['class'];
    }

    public function driversForCategory(DeviceCategory $category): array
    {
        return array_values(array_filter(
            $this->all(),
            fn (array $driver) => $driver['category']->equals($category)
        ));
    }

    public function all(): array
    {
        $result = [];

        foreach ($this->drivers as $vendorKey => $driver) {
            $result[] = [
                'vendor_key' => $vendorKey,
                'driver_class' => $driver['class'],
                'category' => $driver['category'],
            ];
        }

        return $result;
    }
}
