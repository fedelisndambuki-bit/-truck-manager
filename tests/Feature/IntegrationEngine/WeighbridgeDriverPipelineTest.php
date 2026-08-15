<?php

namespace Tests\Feature\IntegrationEngine;

use App\Models\Company;
use App\Models\ExtractionSite;
use App\Models\IntegratedDevice;
use App\Models\WeighbridgeTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tanaki\IntegrationContracts\Contract\DriverFactoryInterface;
use Tanaki\IntegrationContracts\Enum\ConnectionMedium;
use Tanaki\IntegrationContracts\Enum\DeviceCategory;
use Tanaki\IntegrationContracts\Enum\ProtocolType;
use Tanaki\IntegrationContracts\Event\WeighbridgeReadingCaptured;
use Tests\TestCase;

/**
 * End-to-end regression test for the full discovery -> driver -> event ->
 * business-listener pipeline after the API-stability fixes in ADR-0002
 * through ADR-0009. The point of this test is that RecordWeighbridgeReading
 * (the business-side listener) required zero code changes across the whole
 * pass — it should still turn a captured reading into a real
 * WeighbridgeTicket without ever knowing about DeviceCategory, ProtocolType,
 * or any of the shape changes made to the events/DTOs it consumes.
 */
class WeighbridgeDriverPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_generic_http_driver_resolves_and_parses_a_reading(): void
    {
        $driver = app(DriverFactoryInterface::class)->make('generic-http');

        $this->assertSame(DeviceCategory::WEIGHBRIDGE, DeviceCategory::from('weighbridge')->value());
        $this->assertTrue($driver->getCategory()->equals(DeviceCategory::from(DeviceCategory::WEIGHBRIDGE)));

        $dto = $driver->parseReading([
            'company_id' => 1,
            'device_id' => 'device-1',
            'gross_weight' => 32000,
            'tare_weight' => 12000,
            'unit' => 'kg',
            'vehicle_identifier' => 'KAA 999X',
        ]);

        $this->assertSame(20000.0, $dto->netWeight);
        $this->assertInstanceOf(\Carbon\CarbonImmutable::class, $dto->capturedAt);
    }

    public function test_a_captured_reading_produces_a_real_weighbridge_ticket_via_the_unmodified_listener(): void
    {
        config(['queue.default' => 'sync']);

        $company = Company::factory()->create();
        $site = ExtractionSite::factory()->create(['company_id' => $company->id]);

        $device = IntegratedDevice::create([
            'company_id' => $company->id,
            'name' => 'Test Weighbridge',
            'vendor_key' => 'generic-http',
            'category' => DeviceCategory::from(DeviceCategory::WEIGHBRIDGE),
            'connection_medium' => ConnectionMedium::Ethernet,
            'protocol' => ProtocolType::from(ProtocolType::HTTP),
            'connection_config' => ['extraction_site_id' => $site->id],
            'status' => 'active',
        ]);

        $driver = app(DriverFactoryInterface::class)->make('generic-http');

        $dto = $driver->parseReading([
            'company_id' => $company->id,
            'device_id' => (string) $device->id,
            'gross_weight' => 32000,
            'tare_weight' => 12000,
            'unit' => 'kg',
            'vehicle_identifier' => 'KAA 999X',
        ]);

        event(new WeighbridgeReadingCaptured($dto));

        $ticket = WeighbridgeTicket::withoutGlobalScope('company')
            ->where('vehicle_registration', 'KAA 999X')
            ->first();

        $this->assertNotNull($ticket);
        $this->assertSame(32000.0, (float) $ticket->gross_weight);
        $this->assertSame(20000.0, (float) $ticket->net_weight);
    }
}
