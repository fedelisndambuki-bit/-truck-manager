<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\CustomerOrder;
use App\Models\Employee;
use App\Models\ExtractionSite;
use App\Models\LoadingBay;
use App\Models\Material;
use App\Models\ProofOfDelivery;
use App\Models\Staff;
use App\Models\Stockpile;
use App\Models\Truck;
use App\Models\Weighbridge;
use App\Models\WeighbridgeException;
use App\Services\Quarry\DispatchSchedulingService;
use Illuminate\Database\Seeder;

/**
 * Extends the Quarry demo company (Rift Valley Quarries Ltd) with real
 * Dispatch Management data: 2 fleet trucks (this company had none — Smart
 * Weighbridge's demo dispatches all used vehicle_registration as a plain
 * string with no real Truck record), and 2 CustomerOrders taken all the way
 * through Confirmed -> Reserved (real StockReservation) -> linked
 * DispatchOrder created -> scheduled (truck/driver assigned via the real
 * DispatchSchedulingService recommendations) -> through the existing Smart
 * Weighbridge lifecycle (recordTare/loading/recordGross/runDecisionEngine,
 * reusing those exact methods) -> Exited -> updateLiveLocation() ->
 * recordArrival()/markDelayed() -> ProofOfDelivery. One dispatch
 * demonstrates the 'Delayed' status via a real delivery-journey
 * WeighbridgeException submitted through the existing approval workflow.
 * Idempotent.
 */
class DispatchManagementDemoSeeder extends Seeder
{
    public function run(): void
    {
        $northSite = ExtractionSite::withoutGlobalScope('company')->where('name', 'like', '%North Pit%')->first();

        if (! $northSite) {
            $this->command?->warn('Skipping DispatchManagementDemoSeeder: demo quarry site not found (run QuarryDemoSeeder first).');

            return;
        }

        $companyId = $northSite->company_id;

        if (CustomerOrder::withoutGlobalScope('company')->where('company_id', $companyId)->exists()) {
            $this->command?->info('DispatchManagementDemoSeeder: demo data already exists, skipping.');

            return;
        }

        $client = Client::withoutGlobalScope('company')->where('company_id', $companyId)->first();
        $pileA = Stockpile::withoutGlobalScope('company')->where('company_id', $companyId)->where('label', 'Stockyard A — Bay 1')->first();
        $material = Material::withoutGlobalScope('company')->find($pileA->material_id);
        $staff = Staff::withoutGlobalScope('company')->where('company_id', $companyId)->where('full_name', 'John Mwangi')->first();
        $weighbridge = Weighbridge::withoutGlobalScope('company')->where('company_id', $companyId)->first();
        $loadingBay = LoadingBay::withoutGlobalScope('company')->where('company_id', $companyId)->where('stockpile_id', $pileA->id)->first();

        if (! $client || ! $pileA || ! $material || ! $weighbridge) {
            $this->command?->warn('Skipping DispatchManagementDemoSeeder: prerequisite demo records not found (run QuarryDemoSeeder / SmartWeighbridgeDemoSeeder first).');

            return;
        }

        // This company had no real fleet Truck records — Smart Weighbridge's
        // demo dispatches only ever used vehicle_registration as a plain
        // string. Dispatch Management's scheduling needs real Truck rows to
        // demonstrate recommendTruck()'s fitness comparison.
        $truck1 = Truck::create([
            'company_id' => $companyId, 'plate_number' => 'KDQ 501E', 'truck_type' => 'Tipper',
            'capacity_tons' => 32, 'status' => 'Available',
            'insurance_provider' => 'Jubilee Insurance', 'insurance_expiry' => now()->addMonths(8),
            'road_license_expiry' => now()->addMonths(5),
        ]);
        $truck2 = Truck::create([
            'company_id' => $companyId, 'plate_number' => 'KDQ 502F', 'truck_type' => 'Tipper',
            'capacity_tons' => 30, 'status' => 'Available',
            'insurance_provider' => 'Jubilee Insurance', 'insurance_expiry' => now()->addMonths(3),
            'road_license_expiry' => now()->addMonths(6),
        ]);

        $driver1 = Employee::withoutGlobalScope('company')->where('company_id', $companyId)->where('status', 'Active')->first();
        $driver2 = Employee::create([
            'company_id' => $companyId, 'full_name' => 'Kevin Otieno', 'phone_number' => '+254712000501',
            'national_id' => '31000501', 'license_number' => 'DL900501', 'license_expiry' => now()->addYears(2),
            'date_hired' => now()->subYear(), 'emergency_contact_name' => 'Grace Otieno',
            'emergency_contact_phone' => '+254712000502', 'status' => 'Active', 'category' => 'Long Distance',
        ]);

        $scheduling = app(DispatchSchedulingService::class);

        // --- Customer Order 1: full journey through to a completed, on-time delivery ---
        $order1 = CustomerOrder::create([
            'company_id' => $companyId, 'customer_id' => $client->id, 'customer_site' => 'Nakuru Industrial Park, Plot 14',
            'sales_contract_number' => 'CON-Q-2026-014', 'quotation_number' => 'QT-2026-0091',
            'material_id' => $material->id, 'product_grade' => 'Grade A', 'quantity' => 25,
            'unit_price' => $material->selling_price ?? 1800, 'priority' => 'Normal',
            'requested_delivery_date' => now()->addDay(), 'status' => 'Confirmed', 'planned_by_id' => $staff?->id,
        ]);
        $order1->reserveStock($pileA->id);
        $dispatch1 = $order1->createDispatchOrder(['status' => 'Planned']);

        $recommendedTruck1 = $scheduling->recommendTruck($companyId, (float) $dispatch1->target_net_weight);
        $recommendedDriver1 = $scheduling->recommendDriver($companyId);
        $dispatch1->update([
            'vehicle_registration' => $recommendedTruck1?->plate_number ?? $truck1->plate_number,
            'driver_name' => $recommendedDriver1?->full_name ?? $driver1?->full_name,
            'driver_license_number' => $recommendedDriver1?->license_number ?? $driver1?->license_number,
            'driver_phone' => $recommendedDriver1?->phone_number ?? $driver1?->phone_number,
            'weighbridge_id' => $weighbridge->id,
            'loading_bay_id' => $loadingBay?->id,
            'status' => 'Checked In',
            'gate_in_at' => now()->subHours(2),
            'gate' => 'Gate 1',
            'vehicle_inspection_passed' => true,
        ]);
        $dispatch1->recordTare(['tare_weight' => 15, 'tare_operator_id' => $staff?->id]);
        $li1 = $dispatch1->currentLoadingInstruction();
        $li1->accept();
        $li1->startLoading();
        $li1->complete(25);
        $dispatch1->recordGross(['gross_weight' => 39.9, 'gross_operator_id' => $staff?->id]); // net 24.9, within tolerance
        $dispatch1->runDecisionEngine();
        $dispatch1->refresh();
        $dispatch1->recordExit(['exit_gate' => 'Gate 1', 'security_clearance_by_id' => $staff?->id]);
        $dispatch1->updateLiveLocation([
            'current_gps_lat' => -0.303, 'current_gps_lng' => 36.080,
            'distance_remaining_km' => 18.5, 'eta' => now()->addMinutes(35),
        ]);
        $dispatch1->recordArrival(['delivery_notes' => 'Delivered on schedule, receiver confirmed quantity.']);
        ProofOfDelivery::create([
            'company_id' => $companyId, 'dispatch_order_id' => $dispatch1->id, 'delivered_quantity' => $dispatch1->net_weight,
            'delivery_time' => now(), 'receiver_name' => 'Site Foreman — Nakuru Industrial Park', 'status' => 'Delivered',
        ]);

        // --- Customer Order 2: same journey but demonstrates a delayed delivery ---
        $order2 = CustomerOrder::create([
            'company_id' => $companyId, 'customer_id' => $client->id, 'customer_site' => 'Eldoret Ring Road Site B',
            'sales_contract_number' => 'CON-Q-2026-015', 'material_id' => $material->id, 'product_grade' => 'Grade A',
            'quantity' => 15, 'unit_price' => $material->selling_price ?? 1800, 'priority' => 'High',
            'requested_delivery_date' => now()->addDays(2), 'status' => 'Confirmed', 'planned_by_id' => $staff?->id,
        ]);
        $order2->reserveStock($pileA->id);
        $dispatch2 = $order2->createDispatchOrder(['status' => 'Planned']);

        $recommendedTruck2 = $scheduling->recommendTruck($companyId, (float) $dispatch2->target_net_weight);
        $recommendedDriver2 = $scheduling->recommendDriver($companyId);
        $dispatch2->update([
            'vehicle_registration' => $recommendedTruck2?->plate_number ?? $truck2->plate_number,
            'driver_name' => $recommendedDriver2?->full_name ?? $driver2->full_name,
            'driver_license_number' => $recommendedDriver2?->license_number ?? $driver2->license_number,
            'driver_phone' => $recommendedDriver2?->phone_number ?? $driver2->phone_number,
            'weighbridge_id' => $weighbridge->id,
            'loading_bay_id' => $loadingBay?->id,
            'status' => 'Checked In',
            'gate_in_at' => now()->subHours(4),
            'gate' => 'Gate 1',
            'vehicle_inspection_passed' => true,
        ]);
        $dispatch2->recordTare(['tare_weight' => 15, 'tare_operator_id' => $staff?->id]);
        $li2 = $dispatch2->currentLoadingInstruction();
        $li2->accept();
        $li2->startLoading();
        $li2->complete(15);
        $dispatch2->recordGross(['gross_weight' => 29.9, 'gross_operator_id' => $staff?->id]); // net 14.9, within tolerance
        $dispatch2->runDecisionEngine();
        $dispatch2->refresh();
        $dispatch2->recordExit(['exit_gate' => 'Gate 1', 'security_clearance_by_id' => $staff?->id]);
        $dispatch2->updateLiveLocation([
            'current_gps_lat' => -0.166, 'current_gps_lng' => 35.279,
            'distance_remaining_km' => 42.0, 'eta' => now()->addHour(),
        ]);
        $dispatch2->markDelayed('Heavy traffic on the Eldoret bypass — revised ETA pending.');

        $exception = WeighbridgeException::create([
            'company_id' => $companyId, 'dispatch_order_id' => $dispatch2->id, 'exception_type' => 'Traffic Delay',
            'reason' => 'Truck stuck in heavy traffic on the Eldoret bypass, more than an hour behind schedule.',
            'reported_by_id' => $staff?->id, 'status' => 'Draft',
        ]);
        $exception->update(['status' => 'Submitted']);
        $exception->submitForApproval('weighbridge-exception-approval', [
            'title' => 'Delivery Exception — ' . $exception->exception_number,
            'metadata' => ['weighbridge_exception_id' => $exception->id],
        ]);

        $this->command?->info('DispatchManagementDemoSeeder: 2 fleet trucks, 1 additional driver, 2 customer orders taken through the full planning-to-delivery lifecycle (1 on-time delivery, 1 delayed with an open delivery exception) seeded.');
    }
}
