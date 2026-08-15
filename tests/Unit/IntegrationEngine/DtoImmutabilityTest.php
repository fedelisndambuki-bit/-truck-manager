<?php

namespace Tests\Unit\IntegrationEngine;

use Carbon\CarbonImmutable;
use Tanaki\IntegrationContracts\DTO\WeighbridgeReadingDTO;
use Tests\TestCase;

/**
 * Covers ADR-0007: DTO timestamp fields use Carbon\CarbonImmutable, not the
 * mutable Carbon\Carbon. `readonly` alone only stops property reassignment —
 * it does not stop a mutable Carbon object's own methods from mutating it
 * in place, which is the immutability gap this fix closes.
 */
class DtoImmutabilityTest extends TestCase
{
    public function test_captured_at_is_carbon_immutable(): void
    {
        $dto = $this->makeDto(CarbonImmutable::parse('2026-01-01 08:00:00'));

        $this->assertInstanceOf(CarbonImmutable::class, $dto->capturedAt);
    }

    public function test_calling_a_mutator_style_method_does_not_change_the_original_instance(): void
    {
        $original = CarbonImmutable::parse('2026-01-01 08:00:00');
        $dto = $this->makeDto($original);

        // addDay() on CarbonImmutable returns a NEW instance rather than
        // mutating in place — proving the DTO's timestamp can't be silently
        // corrupted by a caller that (mis)treats it like the old Carbon type.
        $dto->capturedAt->addDay();

        $this->assertSame('2026-01-01 08:00:00', $dto->capturedAt->format('Y-m-d H:i:s'));
    }

    public function test_the_dto_property_itself_cannot_be_reassigned(): void
    {
        $dto = $this->makeDto(CarbonImmutable::now());

        $this->expectException(\Error::class);

        // @phpstan-ignore-next-line — deliberately violating readonly to prove it's enforced
        $dto->capturedAt = CarbonImmutable::now();
    }

    private function makeDto(CarbonImmutable $capturedAt): WeighbridgeReadingDTO
    {
        return new WeighbridgeReadingDTO(
            companyId: 1,
            deviceId: 'device-1',
            weighbridgeCode: 'WB-1',
            grossWeight: 100.0,
            tareWeight: 40.0,
            netWeight: 60.0,
            unit: 'kg',
            vehicleIdentifier: 'ABC 123',
            capturedAt: $capturedAt,
        );
    }
}
