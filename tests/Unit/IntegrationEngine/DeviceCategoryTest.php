<?php

namespace Tests\Unit\IntegrationEngine;

use Tanaki\IntegrationContracts\Enum\DeviceCategory;
use Tests\TestCase;

/**
 * Covers ADR-0004: DeviceCategory converted from a closed native enum to an
 * open value object so third-party driver packages can declare categories
 * this codebase has never heard of.
 */
class DeviceCategoryTest extends TestCase
{
    public function test_built_in_constant_resolves_with_its_label(): void
    {
        $category = DeviceCategory::from(DeviceCategory::WEIGHBRIDGE);

        $this->assertSame('weighbridge', $category->value());
        $this->assertSame('Weighbridge', $category->label());
    }

    public function test_all_nine_built_in_constants_resolve(): void
    {
        $constants = [
            DeviceCategory::WEIGHBRIDGE,
            DeviceCategory::GPS_TRACKER,
            DeviceCategory::FUEL_CONTROLLER,
            DeviceCategory::RFID_READER,
            DeviceCategory::BIOMETRIC,
            DeviceCategory::BARCODE_SCANNER,
            DeviceCategory::QR_READER,
            DeviceCategory::PLC,
            DeviceCategory::IOT_SENSOR,
        ];

        foreach ($constants as $constant) {
            $category = DeviceCategory::from($constant);
            $this->assertSame($constant, $category->value());
            $this->assertNotSame('', $category->label());
        }
    }

    public function test_an_unknown_value_becomes_a_valid_custom_category(): void
    {
        // This is the entire point of ADR-0004: a third-party driver for a
        // category Tanaki has never shipped a constant for must still work.
        $category = DeviceCategory::from('thermal_camera');

        $this->assertSame('thermal_camera', $category->value());
        $this->assertSame('Thermal Camera', $category->label());
    }

    public function test_custom_accepts_an_explicit_label(): void
    {
        $category = DeviceCategory::custom('night_vision_camera', 'Night Vision Camera');

        $this->assertSame('night_vision_camera', $category->value());
        $this->assertSame('Night Vision Camera', $category->label());
    }

    public function test_equals_compares_by_value_not_identity(): void
    {
        $a = DeviceCategory::from(DeviceCategory::PLC);
        $b = DeviceCategory::from(DeviceCategory::PLC);
        $c = DeviceCategory::custom('plc'); // same raw value via the other constructor path

        $this->assertTrue($a->equals($b));
        $this->assertTrue($a->equals($c));
        $this->assertFalse($a->equals(DeviceCategory::from(DeviceCategory::IOT_SENSOR)));
    }

    public function test_it_is_stringable(): void
    {
        $category = DeviceCategory::from(DeviceCategory::GPS_TRACKER);

        $this->assertSame('gps_tracker', (string) $category);
    }

    public function test_the_constructor_is_not_publicly_accessible(): void
    {
        $reflection = new \ReflectionClass(DeviceCategory::class);

        $this->assertFalse($reflection->getConstructor()->isPublic());
    }
}
