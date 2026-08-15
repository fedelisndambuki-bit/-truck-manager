<?php

namespace Tanaki\IntegrationContracts\Contract;

use Tanaki\IntegrationContracts\Enum\DeviceCategory;

interface DeviceRegistryInterface
{
    public function register(string $vendorKey, string $driverClass, DeviceCategory $category): void;

    public function isRegistered(string $vendorKey): bool;

    /**
     * @return class-string<DeviceDriverInterface>
     */
    public function driverClassFor(string $vendorKey): string;

    /**
     * @return array<int, array{vendor_key: string, driver_class: class-string<DeviceDriverInterface>, category: DeviceCategory}>
     */
    public function driversForCategory(DeviceCategory $category): array;

    /**
     * @return array<int, array{vendor_key: string, driver_class: class-string<DeviceDriverInterface>, category: DeviceCategory}>
     */
    public function all(): array;
}
