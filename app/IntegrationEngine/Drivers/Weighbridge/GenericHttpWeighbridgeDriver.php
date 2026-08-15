<?php

namespace App\IntegrationEngine\Drivers\Weighbridge;

use App\IntegrationEngine\Drivers\AbstractDeviceDriver;
use App\IntegrationEngine\Protocols\HttpProtocolAdapter;
use Carbon\CarbonImmutable;
use Tanaki\IntegrationContracts\Attribute\IntegrationDriver;
use Tanaki\IntegrationContracts\Contract\Category\WeighbridgeDriverInterface;
use Tanaki\IntegrationContracts\DTO\WeighbridgeReadingDTO;
use Tanaki\IntegrationContracts\Enum\DeviceCategory;
use Tanaki\IntegrationContracts\Exception\InvalidPayloadException;

/**
 * The one fully real, working driver in Phase 1 — proves the whole pipeline
 * (discovery -> registration -> config -> health check -> event -> queue ->
 * business listener) end-to-end without depending on any specific vendor's
 * undocumented protocol. Any weighbridge indicator capable of POSTing a
 * standard JSON payload (or being adapted to do so via a small bridge
 * script) works with this driver. Vendor-specific drivers (Avery, Mettler,
 * etc.) are a documented Phase 2, built once that vendor's real protocol
 * spec or a test device is available.
 */
#[IntegrationDriver(
    vendor: 'generic-http',
    category: DeviceCategory::WEIGHBRIDGE,
    version: '1.0.0',
    capabilities: ['read', 'health_check', 'diagnostics'],
    displayName: 'Generic HTTP Weighbridge',
    description: 'Accepts a standardized JSON payload over HTTP — for any weighbridge indicator or bridge script that can POST JSON.',
)]
class GenericHttpWeighbridgeDriver extends AbstractDeviceDriver implements WeighbridgeDriverInterface
{
    public static function protocolAdapterClass(): string
    {
        return HttpProtocolAdapter::class;
    }

    /**
     * @param  array<string, mixed>  $rawPayload  expected shape: {gross_weight, tare_weight?, unit?, vehicle_identifier?}
     */
    public function parseReading(array $rawPayload): WeighbridgeReadingDTO
    {
        if (! isset($rawPayload['gross_weight']) || ! is_numeric($rawPayload['gross_weight'])) {
            throw InvalidPayloadException::forVendor($this->getVendorKey(), 'Missing or non-numeric "gross_weight" field.');
        }

        if (! isset($rawPayload['company_id']) || ! isset($rawPayload['device_id'])) {
            throw InvalidPayloadException::forVendor($this->getVendorKey(), 'Missing "company_id" or "device_id" — every reading must be attributable to a tenant and a registered device.');
        }

        $this->recordRawPayload($rawPayload);

        $gross = (float) $rawPayload['gross_weight'];
        $tare = isset($rawPayload['tare_weight']) ? (float) $rawPayload['tare_weight'] : null;

        return new WeighbridgeReadingDTO(
            companyId: (int) $rawPayload['company_id'],
            deviceId: (string) $rawPayload['device_id'],
            weighbridgeCode: (string) ($rawPayload['weighbridge_code'] ?? $rawPayload['device_id']),
            grossWeight: $gross,
            tareWeight: $tare,
            netWeight: $tare !== null ? round($gross - $tare, 2) : null,
            unit: (string) ($rawPayload['unit'] ?? 'kg'),
            vehicleIdentifier: $rawPayload['vehicle_identifier'] ?? null,
            capturedAt: isset($rawPayload['captured_at']) ? CarbonImmutable::parse($rawPayload['captured_at']) : CarbonImmutable::now(),
            rawVendorPayload: $rawPayload,
        );
    }
}
