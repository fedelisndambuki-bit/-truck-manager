<?php

namespace Database\Seeders;

use App\Models\BinLocation;
use App\Models\Company;
use App\Models\GoodsReceivedNote;
use App\Models\Material;
use App\Models\MaterialAllocation;
use App\Models\MaterialCategory;
use App\Models\MaterialConsumption;
use App\Models\MaterialDelivery;
use App\Models\MaterialReturn;
use App\Models\MaterialWastage;
use App\Models\PhysicalStockCount;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Site;
use App\Models\Staff;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\WarehouseZone;
use Illuminate\Database\Seeder;

/**
 * Extends the Construction demo company (Company #15) with the Materials &
 * Procurement pipeline that ConstructionDemoSeeder's own Material/
 * MaterialDelivery rows never exercised: real Suppliers, Warehouses,
 * Purchase Requests/Orders, Goods Received Notes (the one real
 * stock-crediting write path in this module), Allocations, Consumption,
 * Returns, Wastage, Adjustments, Transfers and a Physical Stock Count —
 * using the same Material records ConstructionDemoSeeder already created.
 * Idempotent.
 */
class MaterialsProcurementDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::find(15);

        if (! $company || $company->industry !== 'construction') {
            $this->command?->warn('Skipping MaterialsProcurementDemoSeeder: Company #15 not found or not a construction company.');

            return;
        }

        $companyId = $company->id;

        if (Supplier::withoutGlobalScope('company')->where('company_id', $companyId)->exists()) {
            $this->command?->info('MaterialsProcurementDemoSeeder: demo data already present, skipping.');

            return;
        }

        $projects = Project::withoutGlobalScope('company')->where('company_id', $companyId)->get();
        $materials = Material::withoutGlobalScope('company')->where('company_id', $companyId)->get();
        $staff = Staff::withoutGlobalScope('company')->where('company_id', $companyId)->get();

        if ($projects->isEmpty() || $materials->isEmpty()) {
            $this->command?->warn('Skipping MaterialsProcurementDemoSeeder: no Projects/Materials found (run ConstructionDemoSeeder first).');

            return;
        }

        $project = $projects->first();
        $site = Site::withoutGlobalScope('company')->where('project_id', $project->id)->first();
        $inspector = $staff->first();

        // ---- Material categories, linked onto the existing demo materials ----
        $categories = [
            'Cement & Aggregates' => ['Cement (50kg)', 'River Sand', 'Ballast'],
            'Steel & Reinforcement' => ['Steel Rebar 12mm'],
            'Masonry & Roofing' => ['Bricks', 'Roofing Sheets'],
            'Plumbing & Finishes' => ['PVC Pipes 4in', 'Paint (20L)'],
        ];

        foreach ($categories as $name => $materialNames) {
            $category = MaterialCategory::create(['company_id' => $companyId, 'name' => $name]);

            $materials->whereIn('name', $materialNames)->each(
                fn (Material $m) => $m->update(['category_id' => $category->id])
            );
        }

        // ---- Suppliers (converting the free-text names ConstructionDemoSeeder used) ----
        $supplierDefs = [
            ['name' => 'Bamburi Cement Ltd', 'category' => 'Materials', 'rating' => 4.3],
            ['name' => 'Devki Steel Mills', 'category' => 'Materials', 'rating' => 4.1],
            ['name' => 'Nairobi Quarries Co', 'category' => 'Materials', 'rating' => 3.8],
            ['name' => 'Simba Roofing Suppliers', 'category' => 'Materials', 'rating' => 4.0],
        ];

        $suppliers = collect();
        foreach ($supplierDefs as $def) {
            $suppliers->push(Supplier::create([
                'company_id' => $companyId,
                'company_name' => $def['name'],
                'category' => $def['category'],
                'contact_person' => 'Procurement Desk',
                'phone' => '+254704' . random_int(100000, 999999),
                'email' => strtolower(str_replace(' ', '', $def['name'])) . '@example.com',
                'performance_rating' => $def['rating'],
                'delivery_performance_rating' => round($def['rating'] - 0.2, 1),
                'quality_rating' => round($def['rating'] + 0.1, 1),
                'risk_rating' => $def['rating'] >= 4.0 ? 'Low' : 'Medium',
                'status' => 'Active',
            ]));
        }

        // ---- Warehouse + zone + bin (one project site store, real pipeline) ----
        $warehouse = Warehouse::create([
            'company_id' => $companyId,
            'name' => $project->name . ' — Site Store',
            'code' => 'WH-' . $project->id,
            'type' => 'Site Store',
            'location' => $project->site_address,
            'project_id' => $project->id,
            'site_id' => $site?->id,
            'status' => 'Active',
        ]);

        $zone = WarehouseZone::create([
            'company_id' => $companyId,
            'warehouse_id' => $warehouse->id,
            'name' => 'Main Storage',
            'type' => 'Storage',
            'status' => 'Active',
        ]);

        $bin = BinLocation::create([
            'company_id' => $companyId,
            'warehouse_id' => $warehouse->id,
            'zone_id' => $zone->id,
            'code' => 'BIN-A1',
            'aisle' => 'A',
            'rack' => '1',
        ]);

        $second = BinLocation::create([
            'company_id' => $companyId,
            'warehouse_id' => $warehouse->id,
            'zone_id' => $zone->id,
            'code' => 'BIN-A2',
            'aisle' => 'A',
            'rack' => '2',
        ]);

        // A second warehouse purely so a StockTransfer below has a real destination.
        $secondWarehouse = Warehouse::create([
            'company_id' => $companyId,
            'name' => $project->name . ' — Overflow Store',
            'code' => 'WH-' . $project->id . '-2',
            'type' => 'Temporary Storage',
            'location' => $project->site_address,
            'project_id' => $project->id,
            'site_id' => $site?->id,
            'status' => 'Active',
        ]);

        // ---- Purchase Request -> Purchase Order -> Delivery -> GRN, for 3 materials ----
        // PurchaseRequest/PurchaseOrder use HasApprovalWorkflow but have no
        // simple approve() method of their own — direct status assignment is
        // the established seeder-appropriate shortcut (DemoFleetSeeder.php).
        $cement = $materials->firstWhere('name', 'Cement (50kg)');
        $steel = $materials->firstWhere('name', 'Steel Rebar 12mm');
        $sand = $materials->firstWhere('name', 'River Sand');

        $lines = [
            ['material' => $cement, 'supplier' => $suppliers[0], 'qty' => 400],
            ['material' => $steel, 'supplier' => $suppliers[1], 'qty' => 12],
            ['material' => $sand, 'supplier' => $suppliers[2], 'qty' => 60],
        ];

        foreach ($lines as $line) {
            $material = $line['material'];
            $supplier = $line['supplier'];
            $qty = $line['qty'];
            $unitCost = (float) $material->unit_cost;

            $pr = PurchaseRequest::create([
                'company_id' => $companyId,
                'project_id' => $project->id,
                'material_id' => $material->id,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => $qty,
                'priority' => 'Medium',
                'status' => 'Approved',
                'source' => 'Manual',
                'currency' => 'KES',
                'estimated_cost' => $qty * $unitCost,
                'reason' => 'Restock for ' . $project->name,
            ]);

            $po = PurchaseOrder::create([
                'company_id' => $companyId,
                'project_id' => $project->id,
                'supplier_id' => $supplier->id,
                'purchase_request_id' => $pr->id,
                'currency' => 'KES',
                'subtotal' => $qty * $unitCost,
                'tax_amount' => round($qty * $unitCost * 0.16, 2),
                'discount_amount' => 0,
                'total_amount' => round($qty * $unitCost * 1.16, 2),
                'status' => 'Approved',
                'expected_delivery' => now()->addDays(3),
            ]);

            $delivery = MaterialDelivery::create([
                'company_id' => $companyId,
                'material_id' => $material->id,
                'site_id' => $site?->id,
                'project_id' => $project->id,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->company_name,
                'warehouse_id' => $warehouse->id,
                'purchase_order_id' => $po->id,
                'purchase_order_ref' => $po->po_number,
                'quantity' => $qty,
                'delivered_at' => now()->subDays(2),
                'inspection_status' => 'Passed',
                'status' => 'Delivered',
            ]);

            $grn = GoodsReceivedNote::create([
                'company_id' => $companyId,
                'material_delivery_id' => $delivery->id,
                'received_quantity' => $qty,
                'accepted_quantity' => $qty,
                'rejected_quantity' => 0,
                'damaged_quantity' => 0,
                'quality_approved' => true,
                'inspected_by_id' => $inspector?->id,
                'status' => 'Pending',
                'inspection_notes' => 'Received in good condition, matches delivery note.',
            ]);
            $grn->approve(); // credits StockLevel + Material.quantity_on_hand, writes InventoryMovement + a PutAwayTask

            // Complete the PutAwayTask GRN::approve() just created, so BinStock is populated too.
            $task = \App\Models\PutAwayTask::withoutGlobalScope('company')
                ->where('goods_received_note_id', $grn->id)
                ->first();
            $task?->complete($bin, $inspector);
        }

        // ---- Project-attributed allocation + consumption (real stock decrement) ----
        $cementAllocation = MaterialAllocation::create([
            'company_id' => $companyId,
            'material_id' => $cement->id,
            'project_id' => $project->id,
            'site_id' => $site?->id,
            'activity' => 'Foundation works',
            'allocated_quantity' => 250,
            'status' => 'Active',
        ]);

        MaterialConsumption::create([
            'company_id' => $companyId,
            'material_id' => $cement->id,
            'project_id' => $project->id,
            'site_id' => $site?->id,
            'warehouse_id' => $warehouse->id,
            'allocation_id' => $cementAllocation->id,
            'activity' => 'Foundation works',
            'quantity_used' => 120,
            'issued_by_id' => $inspector?->id,
            'date' => now()->subDays(1),
        ]);

        // ---- Return + wastage (both call approve(), both real stock movements) ----
        $steelReturn = MaterialReturn::create([
            'company_id' => $companyId,
            'material_id' => $steel->id,
            'warehouse_id' => $warehouse->id,
            'project_id' => $project->id,
            'site_id' => $site?->id,
            'return_quantity' => 1,
            'condition' => 'Good',
            'reason' => 'Over-ordered for this phase',
            'status' => 'Pending',
        ]);
        $steelReturn->approve();

        $sandWastage = MaterialWastage::create([
            'company_id' => $companyId,
            'material_id' => $sand->id,
            'warehouse_id' => $warehouse->id,
            'project_id' => $project->id,
            'site_id' => $site?->id,
            'waste_type' => 'Overuse',
            'quantity' => 4,
            'reason' => 'Spillage during offloading',
            'status' => 'Pending',
        ]);
        $sandWastage->approve();

        // ---- Adjustment (small correction) ----
        $adjustment = StockAdjustment::create([
            'company_id' => $companyId,
            'material_id' => $cement->id,
            'warehouse_id' => $warehouse->id,
            'adjustment_type' => 'Negative',
            'reason' => 'Damaged',
            'quantity' => 5,
            'approved_by_id' => $inspector?->id,
            'status' => 'Pending',
        ]);
        $adjustment->approve();

        // ---- Transfer between the two warehouses ----
        $transfer = StockTransfer::create([
            'company_id' => $companyId,
            'material_id' => $sand->id,
            'quantity' => 10,
            'source_warehouse_id' => $warehouse->id,
            'source_project_id' => $project->id,
            'destination_warehouse_id' => $secondWarehouse->id,
            'destination_project_id' => $project->id,
            'approved_by_id' => $inspector?->id,
            'status' => 'Pending',
        ]);
        $transfer->approve();

        // ---- Physical stock count, closing the loop with a real variance -> StockAdjustment ----
        $count = PhysicalStockCount::create([
            'company_id' => $companyId,
            'warehouse_id' => $warehouse->id,
            'scheduled_date' => now(),
            'status' => 'Scheduled',
        ]);
        $count->populateFromCurrentStock();
        $count->items()->where('material_id', $steel->id)->first()?->update([
            'counted_quantity' => (float) ($count->items()->where('material_id', $steel->id)->first()->expected_quantity ?? 0) - 1,
        ]);
        $count->complete();

        $this->command?->info('MaterialsProcurementDemoSeeder: procurement pipeline seeded for Company #' . $companyId);
    }
}
