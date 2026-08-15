<?php

namespace Tests\Unit\IntegrationEngine;

use App\Models\Company;
use App\Models\IntegratedDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tanaki\IntegrationContracts\Enum\ConnectionMedium;
use Tanaki\IntegrationContracts\Enum\DeviceCategory;
use Tanaki\IntegrationContracts\Enum\ProtocolType;
use Tests\TestCase;

/**
 * Covers the backward-compatibility guarantee behind ADR-0004: converting
 * DeviceCategory/ProtocolType from native enums to Castable value objects
 * must not require any change to IntegratedDevice's $casts declaration —
 * 'category' => DeviceCategory::class keeps working exactly as it did when
 * DeviceCategory was a native enum.
 */
class IntegratedDeviceCastTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_and_protocol_round_trip_through_the_database_as_value_objects(): void
    {
        $company = Company::factory()->create();

        $device = IntegratedDevice::create([
            'company_id' => $company->id,
            'name' => 'Test Weighbridge',
            'vendor_key' => 'generic-http',
            'category' => DeviceCategory::from(DeviceCategory::WEIGHBRIDGE),
            'connection_medium' => ConnectionMedium::Ethernet,
            'protocol' => ProtocolType::from(ProtocolType::HTTP),
            'status' => 'active',
        ]);

        $this->assertSame('weighbridge', $device->getAttributes()['category']);
        $this->assertSame('http', $device->getAttributes()['protocol']);

        $fresh = IntegratedDevice::withoutGlobalScope('company')->find($device->id);

        $this->assertInstanceOf(DeviceCategory::class, $fresh->category);
        $this->assertSame('weighbridge', $fresh->category->value());
        $this->assertInstanceOf(ProtocolType::class, $fresh->protocol);
        $this->assertSame('http', $fresh->protocol->value());
    }

    public function test_a_custom_category_persists_and_reads_back_correctly(): void
    {
        $company = Company::factory()->create();

        $device = IntegratedDevice::create([
            'company_id' => $company->id,
            'name' => 'Test Thermal Camera',
            'vendor_key' => 'acme-thermal',
            'category' => DeviceCategory::custom('thermal_camera', 'Thermal Camera'),
            'protocol' => ProtocolType::from(ProtocolType::MQTT),
            'status' => 'active',
        ]);

        $fresh = IntegratedDevice::withoutGlobalScope('company')->find($device->id);

        $this->assertSame('thermal_camera', $fresh->category->value());
    }
}
