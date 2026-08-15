<?php

namespace Tanaki\IntegrationContracts\Enum;

use Stringable;

/**
 * An open taxonomy, not a native PHP enum. Native enums cannot be extended
 * by third-party code — a hardware manufacturer building a driver for a
 * category outside the 9 built-ins below (a CCTV/AI-camera driver, for
 * example) would have no legal value to return from
 * DeviceDriverInterface::getCategory(). This class trades native `match`
 * exhaustiveness and `::cases()` for genuine openness: DeviceCategory::custom()
 * lets any driver package declare a category this package has never heard
 * of, without waiting for an integration-contracts release.
 *
 * Built-in values are exposed as string constants (not zero-arg static
 * factory methods) specifically so they remain usable inside
 * #[IntegrationDriver(category: DeviceCategory::WEIGHBRIDGE)] — PHP
 * attribute arguments must be constant expressions, and a class-constant
 * fetch qualifies where a method call would not.
 *
 * Deliberately zero framework dependency: this package is pure PHP, so it
 * cannot implement Laravel's Castable contract. The Eloquent cast for
 * consumers that persist this value lives in the consuming application
 * instead (see App\IntegrationEngine\Casts\DeviceCategoryCast in Tanaki
 * Hub) — that adapter is what keeps this class itself framework-agnostic.
 */
final class DeviceCategory implements Stringable
{
    public const WEIGHBRIDGE = 'weighbridge';

    public const GPS_TRACKER = 'gps_tracker';

    public const FUEL_CONTROLLER = 'fuel_controller';

    public const RFID_READER = 'rfid_reader';

    public const BIOMETRIC = 'biometric';

    public const BARCODE_SCANNER = 'barcode_scanner';

    public const QR_READER = 'qr_reader';

    public const PLC = 'plc';

    public const IOT_SENSOR = 'iot_sensor';

    /** @var array<string, self> memoized built-in instances, so equals()/identity checks are cheap for the common case */
    private static array $builtIns = [];

    private function __construct(
        private readonly string $value,
        private readonly ?string $label,
    ) {
    }

    /**
     * Resolves a raw string (e.g. read back from a driver-discovery cache
     * or a database column) into a DeviceCategory — a known built-in gets
     * its proper label, anything else becomes a valid, first-class custom
     * category.
     */
    public static function from(string $value): self
    {
        return self::knownLabels()[$value] ?? self::custom($value);
    }

    /**
     * The escape hatch for categories this package doesn't know about yet.
     */
    public static function custom(string $value, ?string $label = null): self
    {
        return new self($value, $label);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return $this->label ?? ucwords(str_replace(['_', '-'], ' ', $this->value));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /** @return array<string, self> */
    private static function knownLabels(): array
    {
        return [
            self::WEIGHBRIDGE => self::$builtIns[self::WEIGHBRIDGE] ??= new self(self::WEIGHBRIDGE, 'Weighbridge'),
            self::GPS_TRACKER => self::$builtIns[self::GPS_TRACKER] ??= new self(self::GPS_TRACKER, 'GPS Tracker'),
            self::FUEL_CONTROLLER => self::$builtIns[self::FUEL_CONTROLLER] ??= new self(self::FUEL_CONTROLLER, 'Fuel Controller'),
            self::RFID_READER => self::$builtIns[self::RFID_READER] ??= new self(self::RFID_READER, 'RFID Reader'),
            self::BIOMETRIC => self::$builtIns[self::BIOMETRIC] ??= new self(self::BIOMETRIC, 'Biometric Device'),
            self::BARCODE_SCANNER => self::$builtIns[self::BARCODE_SCANNER] ??= new self(self::BARCODE_SCANNER, 'Barcode Scanner'),
            self::QR_READER => self::$builtIns[self::QR_READER] ??= new self(self::QR_READER, 'QR Reader'),
            self::PLC => self::$builtIns[self::PLC] ??= new self(self::PLC, 'PLC Controller'),
            self::IOT_SENSOR => self::$builtIns[self::IOT_SENSOR] ??= new self(self::IOT_SENSOR, 'IoT Sensor'),
        ];
    }
}
