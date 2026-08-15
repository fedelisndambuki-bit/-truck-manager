<?php

namespace Tests\Unit\IntegrationEngine;

use App\IntegrationEngine\Registry\DeviceRegistry;
use Tanaki\IntegrationContracts\Enum\DeviceCategory;
use Tanaki\IntegrationContracts\Exception\DriverNotFoundException;
use Tanaki\IntegrationContracts\Exception\IntegrationExceptionInterface;
use Tests\TestCase;

/**
 * Covers ADR-0002: DeviceRegistryInterface/DeviceRegistry return plain
 * array, not Illuminate\Support\Collection — the last Laravel Framework
 * dependency removed from the interface layer.
 */
class DeviceRegistryTest extends TestCase
{
    public function test_all_returns_a_plain_array(): void
    {
        $registry = new DeviceRegistry;
        $registry->register('vendor-a', \stdClass::class, DeviceCategory::from(DeviceCategory::WEIGHBRIDGE));

        $result = $registry->all();

        $this->assertIsArray($result);
        $this->assertNotInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(1, $result);
        $this->assertSame('vendor-a', $result[0]['vendor_key']);
    }

    public function test_drivers_for_category_returns_a_plain_array_filtered_by_category(): void
    {
        $registry = new DeviceRegistry;
        $registry->register('weighbridge-vendor', \stdClass::class, DeviceCategory::from(DeviceCategory::WEIGHBRIDGE));
        $registry->register('gps-vendor', \stdClass::class, DeviceCategory::from(DeviceCategory::GPS_TRACKER));

        $result = $registry->driversForCategory(DeviceCategory::from(DeviceCategory::WEIGHBRIDGE));

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('weighbridge-vendor', $result[0]['vendor_key']);
    }

    public function test_drivers_for_category_matches_custom_categories_by_value(): void
    {
        $registry = new DeviceRegistry;
        $registry->register('thermal-vendor', \stdClass::class, DeviceCategory::custom('thermal_camera'));

        $result = $registry->driversForCategory(DeviceCategory::from('thermal_camera'));

        $this->assertCount(1, $result);
    }

    public function test_is_registered_and_driver_class_for(): void
    {
        $registry = new DeviceRegistry;
        $registry->register('vendor-a', \stdClass::class, DeviceCategory::from(DeviceCategory::WEIGHBRIDGE));

        $this->assertTrue($registry->isRegistered('vendor-a'));
        $this->assertSame(\stdClass::class, $registry->driverClassFor('vendor-a'));
    }

    public function test_unknown_vendor_throws_driver_not_found_catchable_via_the_marker_interface(): void
    {
        $registry = new DeviceRegistry;

        $this->expectException(IntegrationExceptionInterface::class);
        $this->expectException(DriverNotFoundException::class);

        $registry->driverClassFor('does-not-exist');
    }
}
