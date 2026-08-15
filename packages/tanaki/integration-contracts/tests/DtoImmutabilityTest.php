<?php

namespace Tanaki\IntegrationContracts\Tests;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use Tanaki\IntegrationContracts\DTO\WeighbridgeReadingDTO;

class DtoImmutabilityTest extends TestCase
{
    public function test_captured_at_is_carbon_immutable_and_the_dto_is_readonly(): void
    {
        $dto = new WeighbridgeReadingDTO(
            companyId: 1,
            deviceId: 'device-1',
            weighbridgeCode: 'WB-1',
            grossWeight: 100.0,
            tareWeight: 40.0,
            netWeight: 60.0,
            unit: 'kg',
            vehicleIdentifier: 'ABC 123',
            capturedAt: CarbonImmutable::parse('2026-01-01 08:00:00'),
        );

        $this->assertInstanceOf(CarbonImmutable::class, $dto->capturedAt);

        $dto->capturedAt->addDay(); // returns a new instance, does not mutate
        $this->assertSame('2026-01-01 08:00:00', $dto->capturedAt->format('Y-m-d H:i:s'));

        $this->expectException(\Error::class);
        $dto->capturedAt = CarbonImmutable::now();
    }
}
