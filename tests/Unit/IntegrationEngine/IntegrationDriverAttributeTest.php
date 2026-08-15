<?php

namespace Tests\Unit\IntegrationEngine;

use Tanaki\IntegrationContracts\Attribute\IntegrationDriver;
use Tanaki\IntegrationContracts\Enum\DeviceCategory;
use Tests\TestCase;

/**
 * Covers ADR-0008 (readonly properties) and the ADR-0004 follow-on change
 * of $category from a DeviceCategory instance to a raw string, required
 * because PHP attribute arguments must be constant expressions and
 * DeviceCategory's constructor is private.
 */
class IntegrationDriverAttributeTest extends TestCase
{
    public function test_all_properties_are_readonly(): void
    {
        $reflection = new \ReflectionClass(IntegrationDriver::class);

        foreach ($reflection->getProperties() as $property) {
            $this->assertTrue($property->isReadOnly(), "{$property->getName()} should be readonly");
        }
    }

    public function test_category_is_a_plain_string_resolvable_via_device_category_from(): void
    {
        $attribute = new IntegrationDriver(
            vendor: 'test-vendor',
            category: DeviceCategory::WEIGHBRIDGE,
        );

        $this->assertIsString($attribute->category);
        $this->assertSame('weighbridge', DeviceCategory::from($attribute->category)->value());
    }

    public function test_a_custom_category_string_works_without_a_declared_constant(): void
    {
        $attribute = new IntegrationDriver(
            vendor: 'test-vendor',
            category: 'thermal_camera',
        );

        $this->assertSame('Thermal Camera', DeviceCategory::from($attribute->category)->label());
    }

    public function test_the_real_shipped_driver_is_still_correctly_tagged(): void
    {
        $reflection = new \ReflectionClass(\App\IntegrationEngine\Drivers\Weighbridge\GenericHttpWeighbridgeDriver::class);
        $attributes = $reflection->getAttributes(IntegrationDriver::class);

        $this->assertCount(1, $attributes);

        $tag = $attributes[0]->newInstance();

        $this->assertSame('generic-http', $tag->vendor);
        $this->assertSame(DeviceCategory::WEIGHBRIDGE, $tag->category);
    }
}
