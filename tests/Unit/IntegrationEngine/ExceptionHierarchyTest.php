<?php

namespace Tests\Unit\IntegrationEngine;

use PHPUnit\Framework\Attributes\DataProvider;
use Tanaki\IntegrationContracts\Exception\DeviceOfflineException;
use Tanaki\IntegrationContracts\Exception\DriverNotFoundException;
use Tanaki\IntegrationContracts\Exception\IntegrationExceptionInterface;
use Tanaki\IntegrationContracts\Exception\InvalidPayloadException;
use Tanaki\IntegrationContracts\Exception\ProtocolNotImplementedException;
use Tests\TestCase;

/**
 * Covers ADR-0009: every exception this engine throws implements the
 * IntegrationExceptionInterface marker, so a driver author can catch any
 * failure from this engine specifically without also catching unrelated
 * RuntimeExceptions in the same try block.
 */
class ExceptionHierarchyTest extends TestCase
{
    #[DataProvider('exceptionProvider')]
    public function test_every_exception_implements_the_marker_interface(\Throwable $exception): void
    {
        $this->assertInstanceOf(IntegrationExceptionInterface::class, $exception);
    }

    public static function exceptionProvider(): array
    {
        return [
            [DeviceOfflineException::forDevice('device-1')],
            [DriverNotFoundException::forVendor('vendor-1')],
            [InvalidPayloadException::forVendor('vendor-1', 'missing field')],
            [ProtocolNotImplementedException::forProtocol('Modbus', ['a modbus library'])],
        ];
    }

    public function test_catching_the_marker_interface_catches_every_concrete_type(): void
    {
        $caught = 0;

        foreach (self::exceptionProvider() as [$exception]) {
            try {
                throw $exception;
            } catch (IntegrationExceptionInterface) {
                $caught++;
            }
        }

        $this->assertSame(4, $caught);
    }
}
