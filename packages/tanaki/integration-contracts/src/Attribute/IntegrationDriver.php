<?php

namespace Tanaki\IntegrationContracts\Attribute;

use Attribute;

/**
 * Tags a driver class for auto-discovery (DriverDiscoveryService scans
 * app/IntegrationEngine/Drivers/**\/*.php and reads this off each class via
 * Reflection) AND doubles as the single source of truth for the driver's
 * metadata/capabilities — AbstractDeviceDriver::getMetadata()/
 * getCapabilities() read it back via reflection rather than requiring every
 * concrete driver to redeclare the same facts twice.
 *
 * $category is a raw string, not a DeviceCategory instance: PHP attribute
 * arguments must be constant expressions, and DeviceCategory's constructor
 * is deliberately private (it's a static-factory-only value object, see
 * DeviceCategory::from()), so it cannot be `new`'d or method-called inline
 * inside an attribute argument list. Pass one of DeviceCategory's class
 * constants (e.g. DeviceCategory::WEIGHBRIDGE — a constant fetch, which IS a
 * valid attribute argument) or any custom string for a category
 * integration-contracts doesn't know about yet. Consumers call
 * DeviceCategory::from($attribute->category) to get the rich value object.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class IntegrationDriver
{
    /**
     * @param  string  $category  one of DeviceCategory's class constants, or any custom category string
     * @param  string[]  $capabilities  e.g. ['read', 'write', 'stream', 'health_check']
     */
    public function __construct(
        public readonly string $vendor,
        public readonly string $category,
        public readonly string $version = '1.0.0',
        public readonly array $capabilities = ['read', 'health_check'],
        public readonly ?string $displayName = null,
        public readonly ?string $description = null,
    ) {
    }
}
