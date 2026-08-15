<?php

namespace Tanaki\IntegrationContracts\Tests;

use PHPUnit\Framework\TestCase;
use Tanaki\IntegrationContracts\Enum\DeviceCategory;

class DeviceCategoryTest extends TestCase
{
    public function test_built_in_constant_resolves_with_its_label(): void
    {
        $category = DeviceCategory::from(DeviceCategory::WEIGHBRIDGE);

        $this->assertSame('weighbridge', $category->value());
        $this->assertSame('Weighbridge', $category->label());
    }

    public function test_an_unknown_value_becomes_a_valid_custom_category(): void
    {
        $category = DeviceCategory::from('thermal_camera');

        $this->assertSame('thermal_camera', $category->value());
        $this->assertSame('Thermal Camera', $category->label());
    }

    public function test_equals_compares_by_value(): void
    {
        $a = DeviceCategory::from(DeviceCategory::PLC);
        $b = DeviceCategory::custom('plc');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals(DeviceCategory::from(DeviceCategory::IOT_SENSOR)));
    }

    public function test_it_is_stringable(): void
    {
        $this->assertSame('gps_tracker', (string) DeviceCategory::from(DeviceCategory::GPS_TRACKER));
    }

    public function test_the_constructor_is_not_publicly_accessible(): void
    {
        $reflection = new \ReflectionClass(DeviceCategory::class);

        $this->assertFalse($reflection->getConstructor()->isPublic());
    }

    public function test_it_does_not_implement_any_laravel_contract(): void
    {
        $reflection = new \ReflectionClass(DeviceCategory::class);

        foreach ($reflection->getInterfaceNames() as $interface) {
            $this->assertStringNotContainsStringIgnoringCase('Illuminate', $interface);
        }
    }
}
