<?php

namespace App\Listeners\Weighbridge;

use App\Models\IntegratedDevice;
use App\Models\WeighbridgeTicket;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Tanaki\IntegrationContracts\Event\WeighbridgeReadingCaptured;
use Throwable;

/**
 * The proof that business modules never touch hardware directly: this is
 * the ONLY place a WeighbridgeReadingCaptured event turns into a real
 * WeighbridgeTicket row. It has never heard of Avery, Mettler, or
 * "generic-http" — it only knows the standardized DTO shape.
 *
 * $tries/$backoff here is a second, independent retry layer from
 * ProcessDeviceReadingJob's — that one protects "turn the DTO into an
 * event"; this one protects the actual business-side database write.
 */
class RecordWeighbridgeReading implements ShouldQueue
{
    public int $tries = 5;

    public array $backoff = [5, 15, 30, 60, 120];

    public function handle(WeighbridgeReadingCaptured $event): void
    {
        $reading = $event->reading;

        $device = IntegratedDevice::withoutGlobalScope('company')->find($reading->deviceId);

        if (! $device) {
            Log::channel('integration-engine')->warning('WeighbridgeReadingCaptured for an unknown device — skipping.', [
                'device_id' => $reading->deviceId,
            ]);

            return;
        }

        // A weighbridge is a fixed installation, so which extraction site it
        // belongs to lives on the device record, not the reading itself —
        // weighbridge_tickets.extraction_site_id is a required column.
        $extractionSiteId = $device->connection_config['extraction_site_id'] ?? null;

        if (! $extractionSiteId) {
            Log::channel('integration-engine')->warning('Weighbridge device has no extraction_site_id configured — cannot create a ticket without it.', [
                'device_id' => $device->id,
                'device_name' => $device->name,
            ]);

            return;
        }

        WeighbridgeTicket::create([
            'company_id' => $reading->companyId,
            'extraction_site_id' => $extractionSiteId,
            'direction' => 'Outbound',
            'vehicle_registration' => $reading->vehicleIdentifier,
            'gross_weight' => $reading->grossWeight,
            'tare_weight' => $reading->tareWeight,
            'net_weight' => $reading->netWeight,
            'status' => 'Weighed',
            'gate_in_at' => $reading->capturedAt,
            'is_manual_override' => false,
        ]);
    }

    public function failed(WeighbridgeReadingCaptured $event, ?Throwable $exception): void
    {
        Log::channel('integration-engine')->error('RecordWeighbridgeReading permanently failed after all retries', [
            'device_id' => $event->reading->deviceId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
