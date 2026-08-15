<?php

namespace Database\Seeders;

use App\Models\CrushingPlant;
use App\Models\ExtractionSite;
use App\Models\Material;
use App\Models\ProductionStockTransfer;
use App\Models\Staff;
use App\Models\StockMovement;
use App\Models\StockpileAdjustment;
use App\Models\StockpileDocument;
use App\Models\StockpilePhysicalCount;
use App\Models\Stockpile;
use App\Models\StockReservation;
use App\Models\User;
use App\Models\WeighbridgeTicket;
use App\Services\WorkflowEngine;
use Illuminate\Database\Seeder;

/**
 * Extends the Quarry demo company (Rift Valley Quarries Ltd) with real
 * Stockpile Management data: enriches the existing demo Stockpile rows with
 * the new fields, adds Depot/Storage Area stockpiles, extends Material demo
 * rows with grade/selling_unit/quality_standard, seeds stock movements, two
 * full-lifecycle Stock Transfers (Draft -> Submitted -> Approved -> Received
 * -> Completed via the real WorkflowEngine), a Reservation converted to
 * dispatch, an approved Stock Adjustment, an approved Physical Stock Count,
 * a document, and links a demo Outbound WeighbridgeTicket to a stockpile so
 * it genuinely draws down inventory. Idempotent.
 */
class StockpileManagementDemoSeeder extends Seeder
{
    public function run(): void
    {
        $northSite = ExtractionSite::withoutGlobalScope('company')->where('name', 'like', '%North Pit%')->first();

        if (! $northSite) {
            $this->command?->warn('Skipping StockpileManagementDemoSeeder: demo quarry site not found (run QuarryDemoSeeder first).');

            return;
        }

        $companyId = $northSite->company_id;

        if (Stockpile::withoutGlobalScope('company')->where('company_id', $companyId)->whereNotNull('stockyard')->exists()) {
            $this->command?->info('StockpileManagementDemoSeeder: demo data already exists, skipping.');

            return;
        }

        if (! User::withoutGlobalScope('company')->where('email', 'inventory.controller@riftvalleyquarries.example')->exists()) {
            $inventoryController = User::create([
                'company_id' => $companyId, 'name' => 'Esther Achieng',
                'email' => 'inventory.controller@riftvalleyquarries.example', 'password' => 'password', 'email_verified_at' => now(),
            ]);
            $inventoryController->assignRole('Inventory Controller');
        }
        $inventoryController = User::withoutGlobalScope('company')->where('email', 'inventory.controller@riftvalleyquarries.example')->first();

        $northPlant = CrushingPlant::withoutGlobalScope('company')->where('extraction_site_id', $northSite->id)->first();
        $staff = Staff::withoutGlobalScope('company')->where('company_id', $companyId)->first();
        $user = User::withoutGlobalScope('company')->where('company_id', $companyId)->first();

        // Enrich the existing demo Stockpiles with the new fields
        $bay1 = Stockpile::withoutGlobalScope('company')->where('company_id', $companyId)->where('label', 'Stockyard A — Bay 1')->first();
        $bay2 = Stockpile::withoutGlobalScope('company')->where('company_id', $companyId)->where('label', 'Stockyard A — Bay 2')->first();
        $bay3 = Stockpile::withoutGlobalScope('company')->where('company_id', $companyId)->where('label', 'Stockyard A — Bay 3')->first();
        $bay4 = Stockpile::withoutGlobalScope('company')->where('company_id', $companyId)->where('label', 'Stockyard B — Bay 1')->first();

        foreach ([$bay1, $bay2, $bay3, $bay4] as $index => $pile) {
            $pile?->update([
                'stockpile_code' => $pile->stockpile_code ?? ('STK-' . str_pad((string) ($pile->id), 4, '0', STR_PAD_LEFT)),
                'stockyard' => $pile->label,
                'location_type' => 'Stockyard',
                'gps_lat' => -1.286 + ($index * 0.001),
                'gps_lng' => 36.817 + ($index * 0.001),
                'status' => 'Active',
                'crushing_plant_id' => $northPlant?->id,
                'quality_status' => 'Approved',
            ]);
        }

        // A Depot and a Storage Area stockpile, to demonstrate the full location-type range
        $depot = Stockpile::create([
            'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'material_id' => $bay1->material_id,
            'label' => 'Nairobi Depot — Yard 1', 'stockyard' => 'Nairobi Depot', 'location_type' => 'Depot',
            'gps_lat' => -1.2921, 'gps_lng' => 36.8219, 'quantity_available' => 60, 'min_level' => 20, 'max_level' => 300,
            'unit_value' => 1800, 'status' => 'Active', 'quality_status' => 'Approved',
        ]);
        $storageArea = Stockpile::create([
            'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'material_id' => $bay3->material_id,
            'label' => 'Covered Storage Area 1', 'stockyard' => 'Covered Storage Area 1', 'location_type' => 'Storage Area',
            'quantity_available' => 40, 'min_level' => 10, 'max_level' => 150,
            'unit_value' => 1000, 'status' => 'Active', 'quality_status' => 'Pending Test',
        ]);

        // Extend Material demo rows with grade/selling_unit/quality_standard
        foreach (Material::withoutGlobalScope('company')->where('company_id', $companyId)->whereIn('id', [
            $bay1->material_id, $bay2->material_id, $bay3->material_id, $bay4->material_id,
        ])->get() as $material) {
            $material->update([
                'grade' => match (true) {
                    str_contains($material->name, '20mm') => '20 mm Aggregate',
                    str_contains($material->name, '40mm') => '40 mm Aggregate',
                    str_contains($material->name, 'Dust') => 'Crusher Dust',
                    default => 'Building Stone',
                },
                'selling_unit' => 'per tonne',
                'quality_standard' => 'KS 02-1070',
            ]);
        }

        // Stock movements — a production receipt and a waste entry
        StockMovement::create([
            'company_id' => $companyId, 'movement_type' => 'Production Receipt', 'material_id' => $bay1->material_id,
            'quantity' => 50, 'destination_stockpile_id' => $bay1->id, 'operator_id' => $staff?->id, 'date' => now()->subDays(1),
        ]);
        StockMovement::create([
            'company_id' => $companyId, 'movement_type' => 'Waste', 'material_id' => $bay3->material_id,
            'quantity' => 3, 'source_stockpile_id' => $bay3->id, 'operator_id' => $staff?->id, 'date' => now()->subDays(2),
        ]);
        $bay3->decrement('quantity_available', 3);

        $engine = app(WorkflowEngine::class);

        // Two full-lifecycle Stock Transfers: Draft -> Submitted -> Approved -> Received -> Completed
        foreach ([1, 2] as $i) {
            $transfer = ProductionStockTransfer::create([
                'company_id' => $companyId, 'material_id' => $bay1->material_id, 'quantity' => 20 + $i,
                'source_stockpile_id' => $bay1->id, 'destination_stockpile_id' => $depot->id,
                'driver_name' => 'Transfer Driver ' . $i, 'vehicle_registration' => 'KD' . (900 + $i) . 'Q',
                'operator_id' => $staff?->id, 'status' => 'Pending',
            ]);

            $transfer->update(['status' => 'Submitted']);
            $request = $transfer->submitForApproval('stock-transfer-approval', [
                'title' => 'Stock Transfer — ' . $transfer->transfer_number,
                'metadata' => ['production_stock_transfer_id' => $transfer->id],
            ]);

            $step = $request->steps()->where('status', 'Pending')->first();
            $action = $step?->actions()->whereNull('action')->first();

            if ($action) {
                $engine->approve($action);
            }

            $transfer->update(['received_status' => 'Received', 'received_by_id' => $staff?->id, 'received_at' => now()]);
            $transfer->complete();
        }

        // A Reservation converted to dispatch
        $reservation = StockReservation::create([
            'company_id' => $companyId, 'stockpile_id' => $bay1->id, 'customer_id' => null,
            'order_number' => 'ORD-2001', 'quantity_reserved' => 25, 'reservation_date' => now()->subDays(3),
            'expiry_date' => now()->addDays(7), 'reserved_by_id' => $staff?->id, 'status' => 'Reserved',
        ]);
        $reservation->convertToDispatch();

        // An approved Stock Adjustment
        $adjustment = StockpileAdjustment::create([
            'company_id' => $companyId, 'stockpile_id' => $bay2->id, 'quantity' => -2,
            'reason' => 'Spillage', 'requested_by_id' => $staff?->id, 'status' => 'Draft',
        ]);
        $adjustment->update(['status' => 'Submitted']);
        $adjRequest = $adjustment->submitForApproval('stock-adjustment-approval', [
            'title' => 'Stock Adjustment — ' . $adjustment->adjustment_number,
            'metadata' => ['stockpile_adjustment_id' => $adjustment->id],
        ]);
        $adjStep = $adjRequest->steps()->where('status', 'Pending')->first();
        $adjAction = $adjStep?->actions()->whereNull('action')->first();
        if ($adjAction) {
            $engine->approve($adjAction);
        }

        StockpileDocument::create([
            'company_id' => $companyId, 'documentable_type' => StockpileAdjustment::class, 'documentable_id' => $adjustment->id,
            'category' => 'Adjustment Evidence', 'file_path' => 'stockpile-documents/demo-spillage-photo.txt', 'uploaded_by_id' => $user?->id,
        ]);

        // An approved Physical Stock Count — proves real variance reconciliation
        $count = StockpilePhysicalCount::create([
            'company_id' => $companyId, 'stockpile_id' => $bay4->id, 'physical_quantity' => (float) $bay4->quantity_available - 4,
            'survey_method' => 'Drone Survey', 'count_date' => now(), 'counted_by_id' => $staff?->id, 'status' => 'Draft',
        ]);
        $count->update(['status' => 'Submitted']);
        $countRequest = $count->submitForApproval('physical-count-approval', [
            'title' => 'Physical Stock Count — ' . $count->count_number,
            'metadata' => ['stockpile_physical_count_id' => $count->id],
        ]);
        $countStep = $countRequest->steps()->where('status', 'Pending')->first();
        $countAction = $countStep?->actions()->whereNull('action')->first();
        if ($countAction) {
            $engine->approve($countAction);
        }

        // A document on the Stockyard A — Bay 1 stockpile itself
        StockpileDocument::create([
            'company_id' => $companyId, 'documentable_type' => Stockpile::class, 'documentable_id' => $bay1->id,
            'category' => 'Survey Report', 'file_path' => 'stockpile-documents/demo-survey-report.txt', 'uploaded_by_id' => $user?->id,
        ]);

        // Link a demo Outbound WeighbridgeTicket to a stockpile — confirms real dispatch draw-down
        $ticket = WeighbridgeTicket::create([
            'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'direction' => 'Outbound',
            'material_id' => $bay1->material_id, 'stockpile_id' => $bay1->id,
            'gross_weight' => 32.5, 'tare_weight' => 11.2, 'operator_id' => $staff?->id, 'status' => 'Pending',
        ]);
        $ticket->update(['status' => 'Weighed']);

        $this->command?->info('StockpileManagementDemoSeeder: stockpiles enriched, depot/storage-area stockpiles added, materials graded, movements, 2 approved+completed transfers, a converted reservation, an approved adjustment, an approved physical count, documents and an outbound weighbridge dispatch seeded.');
    }
}
