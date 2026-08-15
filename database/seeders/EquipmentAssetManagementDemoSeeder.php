<?php

namespace Database\Seeders;

use App\Models\Breakdown;
use App\Models\DepreciationSchedule;
use App\Models\EamStore;
use App\Models\Equipment;
use App\Models\EquipmentAttachment;
use App\Models\EquipmentDocument;
use App\Models\EquipmentHealthReading;
use App\Models\EquipmentUtilizationLog;
use App\Models\ExtractionSite;
use App\Models\MaintenanceWorkOrder;
use App\Models\Part;
use App\Models\PreventiveMaintenancePlan;
use App\Models\Staff;
use App\Models\TelematicsReading;
use App\Models\Tyre;
use App\Models\TyreRotationLog;
use App\Models\User;
use App\Models\WarrantyClaim;
use App\Models\Workshop;
use App\Models\WorkshopBay;
use App\Services\BreakdownAssignmentService;
use App\Services\DepreciationService;
use App\Services\MaintenanceWorkOrderService;
use Illuminate\Database\Seeder;

/**
 * Extends the Quarry demo company (Rift Valley Quarries Ltd) with a full
 * Equipment & Asset Management lifecycle: stores, workshop + bay, an
 * enriched demo equipment record, a preventive maintenance plan, a full
 * breakdown -> work order -> close cycle (proving parts deduction, service
 * history and cost/ledger posting all work together), a depreciation
 * schedule with one posted period, a warranty claim, telematics/health
 * readings, utilization logs, a tyre with rotation history, an attachment,
 * and a document. Idempotent, standalone (run manually, not auto-wired).
 */
class EquipmentAssetManagementDemoSeeder extends Seeder
{
    public function run(): void
    {
        $site = ExtractionSite::withoutGlobalScope('company')->where('name', 'like', '%North Pit%')->first();

        if (! $site) {
            $this->command?->warn('Skipping EquipmentAssetManagementDemoSeeder: demo quarry site not found (run QuarryDemoSeeder first).');

            return;
        }

        $companyId = $site->company_id;

        $equipment = Equipment::withoutGlobalScope('company')->where('company_id', $companyId)->where('name', 'like', '%Wheel Loader%')->first();
        $operator = Staff::withoutGlobalScope('company')->where('company_id', $companyId)->first();
        $technician = Staff::withoutGlobalScope('company')->where('company_id', $companyId)->skip(1)->first();

        if (! $equipment || ! $operator || ! $technician) {
            $this->command?->warn('Skipping EquipmentAssetManagementDemoSeeder: demo equipment/staff not found (run QuarryDemoSeeder first).');

            return;
        }

        if (Workshop::withoutGlobalScope('company')->where('company_id', $companyId)->exists()) {
            $this->command?->info('EquipmentAssetManagementDemoSeeder: demo data already exists, skipping.');

            return;
        }

        // --- Stores & Workshop ---
        $store = EamStore::create(['company_id' => $companyId, 'extraction_site_id' => $site->id, 'name' => 'North Pit Main Store', 'location' => 'North Pit Yard', 'type' => 'Store']);
        $depot = EamStore::create(['company_id' => $companyId, 'extraction_site_id' => $site->id, 'name' => 'North Pit Fuel Depot', 'location' => 'North Pit Yard', 'type' => 'Depot']);

        $workshop = Workshop::create(['company_id' => $companyId, 'extraction_site_id' => $site->id, 'name' => 'North Pit Workshop', 'location' => 'North Pit Yard', 'capacity_bays' => 3, 'status' => 'Active']);
        $bay = WorkshopBay::create(['company_id' => $companyId, 'workshop_id' => $workshop->id, 'bay_number' => 'Bay 1', 'status' => 'Available']);

        // --- Enrich the demo equipment record ---
        $equipment->update([
            'eam_store_id' => $store->id,
            'manufacturer' => 'Caterpillar',
            'brand' => 'CAT',
            'model' => '950 GC',
            'year' => 2021,
            'engine_number' => 'ENG-950GC-0042',
            'vin' => 'CAT0950GC0000042',
            'commission_date' => now()->subYears(3),
            'supplier' => 'Mantrac Kenya',
            'replacement_cost' => 18500000,
            'useful_life_years' => 8,
            'residual_value' => 2000000,
        ]);

        // --- Spare part ---
        $part = Part::create([
            'company_id' => $companyId, 'eam_store_id' => $store->id, 'name' => 'Hydraulic Filter', 'part_number' => 'HYD-FLT-950GC',
            'category' => 'Filters', 'unit_price' => 4500, 'stock_quantity' => 20, 'reorder_level' => 5, 'min_stock' => 5, 'max_stock' => 40,
            'bin_location' => 'A1-03', 'supplier' => 'Mantrac Kenya', 'supplier_contact' => 'parts@mantrac.example',
        ]);

        // --- Preventive maintenance plan ---
        PreventiveMaintenancePlan::create([
            'company_id' => $companyId, 'equipment_id' => $equipment->id, 'name' => '250-Hour Service',
            'trigger_type' => 'EngineHours', 'interval_value' => 250,
            'last_triggered_at_value' => (float) $equipment->engine_hours - 200,
            'next_due_value' => (float) $equipment->engine_hours + 50,
            'checklist' => ['Engine oil' => 'Check/Replace', 'Hydraulic filter' => 'Replace', 'Tyre pressure' => 'Check'],
            'required_parts' => [['part_id' => $part->id, 'quantity' => 1]],
            'estimated_duration_hours' => 3,
            'status' => 'Active',
        ]);

        // --- Full breakdown -> work order -> close lifecycle ---
        $breakdown = Breakdown::create([
            'company_id' => $companyId, 'equipment_id' => $equipment->id, 'operator_id' => $operator->id,
            'location' => 'North Pit — Bench 3', 'occurred_at' => now()->subDays(2),
            'severity' => 'High', 'description' => 'Hydraulic pressure loss during loading.',
            'status' => 'Reported',
        ]);

        $assignedTechnician = app(BreakdownAssignmentService::class)->autoAssignTechnician($breakdown) ?? $technician;

        $workOrder = MaintenanceWorkOrder::create([
            'company_id' => $companyId, 'equipment_id' => $equipment->id, 'technician_id' => $assignedTechnician->id,
            'workshop_id' => $workshop->id, 'workshop_bay_id' => $bay->id,
            'work_order_type' => 'Breakdown', 'priority' => 'High', 'status' => 'In Progress',
            'description' => "Breakdown {$breakdown->breakdown_number}: hydraulic pressure loss.",
            'estimated_hours' => 4, 'labor_cost' => 8000,
            'required_parts' => [['part_id' => $part->id, 'quantity' => 1]],
        ]);

        $breakdown->update(['status' => 'Assigned', 'maintenance_work_order_id' => $workOrder->id]);

        app(MaintenanceWorkOrderService::class)->complete($workOrder, 3.5);
        $closedByUser = User::withoutGlobalScope('company')->where('company_id', $companyId)->first();
        app(MaintenanceWorkOrderService::class)->close($workOrder, $closedByUser->id);
        $breakdown->update(['status' => 'Resolved']);

        // --- Depreciation ---
        $schedule = DepreciationSchedule::create([
            'company_id' => $companyId, 'equipment_id' => $equipment->id, 'method' => 'StraightLine',
            'cost_basis' => (float) $equipment->purchase_cost ?: 18000000, 'residual_value' => 2000000,
            'useful_life_years' => 8, 'start_date' => $equipment->commission_date ?? now()->subYears(3), 'status' => 'Active',
        ]);
        app(DepreciationService::class)->postPeriod($schedule, now()->endOfMonth());

        // --- Warranty ---
        WarrantyClaim::create([
            'company_id' => $companyId, 'equipment_id' => $equipment->id, 'covered_component' => 'Hydraulic Pump',
            'issue_description' => 'Pump underperforming under load, suspected manufacturing defect.',
            'filed_at' => now()->subDays(10), 'status' => 'Approved', 'supplier_contact' => 'warranty@mantrac.example',
        ]);

        // --- Telematics & health ---
        TelematicsReading::create([
            'company_id' => $companyId, 'equipment_id' => $equipment->id, 'source' => 'CAT-ProductLink',
            'recorded_at' => now()->subHour(), 'gps_lat' => -0.4167, 'gps_lng' => 36.9500,
            'engine_hours' => $equipment->engine_hours, 'fuel_level' => 68, 'idle_minutes' => 22, 'speed_kph' => 0, 'geofence_status' => 'Inside',
        ]);

        EquipmentHealthReading::create([
            'company_id' => $companyId, 'equipment_id' => $equipment->id, 'recorded_at' => now()->subHour(),
            'engine_temp' => 88, 'oil_pressure' => 42, 'hydraulic_pressure' => 2100, 'coolant_temp' => 85,
            'battery_voltage' => 24.6, 'rpm' => 1450, 'engine_hours' => $equipment->engine_hours,
            'transmission_status' => 'Normal', 'fuel_level' => 68, 'def_level' => 55, 'hydraulic_oil_level' => 90,
            'health_score' => 87,
        ]);

        // --- Utilization logs (last 5 days) ---
        for ($i = 4; $i >= 0; $i--) {
            EquipmentUtilizationLog::create([
                'company_id' => $companyId, 'equipment_id' => $equipment->id, 'log_date' => now()->subDays($i),
                'engine_hours' => (float) $equipment->engine_hours - $i, 'operating_hours' => 8, 'idle_hours' => 1.5,
                'travel_hours' => 0.5, 'standby_hours' => 0, 'trips' => 12, 'cycles' => 24,
                'tonnes_moved' => 240, 'material_loaded' => 240, 'fuel_used_litres' => 65, 'distance_km' => 8,
            ]);
        }

        // --- Tyre + rotation ---
        $tyre = Tyre::create([
            'company_id' => $companyId, 'equipment_id' => $equipment->id, 'serial_number' => 'TYR-950GC-FL01',
            'brand' => 'Michelin', 'size' => '26.5R25', 'type' => 'New', 'position' => 'Front Left',
            'status' => 'Active', 'installation_date' => now()->subMonths(6), 'pressure' => 45,
            'expected_life_hours' => 6000, 'cost_per_hour' => 12.5,
        ]);
        TyreRotationLog::create([
            'company_id' => $companyId, 'tyre_id' => $tyre->id, 'from_position' => 'Rear Left', 'to_position' => 'Front Left',
            'rotated_at' => now()->subMonths(1), 'performed_by_id' => $technician->id, 'notes' => 'Routine rotation.',
        ]);

        // --- Attachment ---
        EquipmentAttachment::create([
            'company_id' => $companyId, 'name' => 'GP Bucket 3.5m³', 'attachment_type' => 'Bucket',
            'serial_number' => 'BKT-950GC-01', 'compatible_equipment_types' => ['Wheel Loaders'],
            'current_equipment_id' => $equipment->id, 'status' => 'In Use', 'purchase_date' => now()->subYears(2), 'purchase_cost' => 850000,
        ]);

        // --- Document ---
        EquipmentDocument::create([
            'company_id' => $companyId, 'title' => 'Operator Manual — CAT 950 GC', 'category' => 'Manual',
            'documentable_type' => Equipment::class, 'documentable_id' => $equipment->id,
            'file_path' => 'equipment-documents/demo-cat-950gc-manual.pdf', 'uploaded_by_id' => $closedByUser->id,
        ]);

        $this->command?->info('EquipmentAssetManagementDemoSeeder: demo data created for ' . $equipment->name . '.');
    }
}
