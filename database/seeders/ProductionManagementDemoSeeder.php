<?php

namespace Database\Seeders;

use App\Models\CrushingPlant;
use App\Models\Equipment;
use App\Models\ExtractionSite;
use App\Models\Material;
use App\Models\Pit;
use App\Models\ProductionDowntime;
use App\Models\ProductionLine;
use App\Models\ProductionLog;
use App\Models\ProductionOutput;
use App\Models\ProductionPlan;
use App\Models\ProductionStockTransfer;
use App\Models\ProductionTarget;
use App\Models\QuarryFace;
use App\Models\Staff;
use App\Models\Stockpile;
use App\Models\User;
use App\Services\WorkflowEngine;
use Illuminate\Database\Seeder;

/**
 * Extends the Quarry demo company (Rift Valley Quarries Ltd) with real
 * Production Management data: quarry faces, pits, production lines, a
 * production plan, downtime events, a production target, a stock transfer,
 * and two full-lifecycle production records taken all the way through
 * Draft -> Submitted -> Approved via the real WorkflowEngine so Stockpile
 * levels genuinely increase. Idempotent.
 */
class ProductionManagementDemoSeeder extends Seeder
{
    public function run(): void
    {
        $northSite = ExtractionSite::withoutGlobalScope('company')->where('name', 'like', '%North Pit%')->first();
        $southSite = ExtractionSite::withoutGlobalScope('company')->where('name', 'like', '%South Pit%')->first();

        if (! $northSite || ! $southSite) {
            $this->command?->warn('Skipping ProductionManagementDemoSeeder: demo quarry sites not found (run QuarryDemoSeeder first).');

            return;
        }

        $companyId = $northSite->company_id;

        if (QuarryFace::withoutGlobalScope('company')->where('company_id', $companyId)->exists()) {
            $this->command?->info('ProductionManagementDemoSeeder: demo data already exists, skipping.');

            return;
        }

        if (! User::withoutGlobalScope('company')->where('email', 'production.manager@riftvalleyquarries.example')->exists()) {
            $productionManager = User::create([
                'company_id' => $companyId, 'name' => 'Daniel Omondi',
                'email' => 'production.manager@riftvalleyquarries.example', 'password' => 'password', 'email_verified_at' => now(),
            ]);
            $productionManager->assignRole('Production Manager');
        }

        $northPlant = CrushingPlant::withoutGlobalScope('company')->where('extraction_site_id', $northSite->id)->first();
        $southPlant = CrushingPlant::withoutGlobalScope('company')->where('extraction_site_id', $southSite->id)->first();
        $crusherOperator = Staff::withoutGlobalScope('company')->where('company_id', $companyId)->where('full_name', 'John Mwangi')->first();
        $weighbridgeStaff = Staff::withoutGlobalScope('company')->where('company_id', $companyId)->where('full_name', 'Grace Wanjiku')->first();
        $loader = Equipment::withoutGlobalScope('company')->where('company_id', $companyId)->where('name', 'like', '%Loader%')->first();
        $aggregate20 = Material::withoutGlobalScope('company')->where('company_id', $companyId)->where('name', 'like', '%20mm%')->first();
        $northAggregate20Pile = Stockpile::withoutGlobalScope('company')->where('extraction_site_id', $northSite->id)->where('material_id', $aggregate20->id)->first();

        // Quarry Faces — 2 per site
        $faces = collect();
        foreach ([$northSite, $southSite] as $index => $site) {
            foreach (['A', 'B'] as $letter) {
                $faces->push(QuarryFace::create([
                    'company_id' => $companyId, 'extraction_site_id' => $site->id,
                    'face_code' => 'FACE-' . strtoupper(substr($site->name, -1)) . $letter,
                    'name' => $site->name . ' — Face ' . $letter,
                    'location' => 'Bench ' . rand(1, 4),
                    'material_type' => $site->material_type,
                    'estimated_reserve' => rand(50000, 150000),
                    'status' => 'Active',
                ]));
            }
        }

        // Pits — 2 per site
        $pits = collect();
        foreach ([$northSite, $southSite] as $site) {
            for ($i = 1; $i <= 2; $i++) {
                $pits->push(Pit::create([
                    'company_id' => $companyId, 'extraction_site_id' => $site->id,
                    'name' => $site->name . ' — Pit ' . $i,
                    'depth' => rand(15, 45), 'bench_level' => 'Level ' . $i,
                    'material_type' => $site->material_type, 'reserve_estimate' => rand(40000, 120000),
                    'current_production' => rand(500, 2000), 'safety_status' => 'Safe',
                ]));
            }
        }

        // Production Lines — one per plant
        $northLine = ProductionLine::create([
            'company_id' => $companyId, 'crushing_plant_id' => $northPlant->id, 'name' => 'North Line 1',
            'capacity_tph' => 250, 'status' => 'Running', 'operator_id' => $crusherOperator->id,
        ]);
        ProductionLine::create([
            'company_id' => $companyId, 'crushing_plant_id' => $southPlant->id, 'name' => 'South Line 1',
            'capacity_tph' => 150, 'status' => 'Idle', 'operator_id' => $crusherOperator->id,
        ]);

        // Production Plan
        ProductionPlan::create([
            'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'crushing_plant_id' => $northPlant->id,
            'material_id' => $aggregate20->id, 'target_quantity' => 400, 'planned_date' => now()->addDays(2),
            'priority' => 'High', 'supervisor_id' => $crusherOperator->id, 'equipment_id' => $loader?->id,
            'assigned_crew' => [$crusherOperator->id, $weighbridgeStaff->id], 'status' => 'Planned',
        ]);

        // Downtime events
        ProductionDowntime::create([
            'company_id' => $companyId, 'crushing_plant_id' => $northPlant->id, 'reason' => 'Mechanical Failure',
            'start_time' => now()->subDays(2)->setTime(9, 0), 'end_time' => now()->subDays(2)->setTime(10, 30),
            'root_cause' => 'Conveyor belt misalignment', 'corrective_action' => 'Belt realigned and tensioned',
            'reported_by_id' => $crusherOperator->id,
        ]);
        ProductionDowntime::create([
            'company_id' => $companyId, 'crushing_plant_id' => $southPlant->id, 'reason' => 'Scheduled Maintenance',
            'start_time' => now()->subDay()->setTime(6, 0), 'end_time' => now()->subDay()->setTime(8, 0),
            'root_cause' => 'Routine crusher liner inspection', 'corrective_action' => 'Liners inspected, no replacement needed',
            'reported_by_id' => $crusherOperator->id,
        ]);

        // Production Target (this month, north site)
        ProductionTarget::create([
            'company_id' => $companyId, 'extraction_site_id' => $northSite->id,
            'period_type' => 'Monthly', 'period_start' => now()->startOfMonth(), 'period_end' => now()->endOfMonth(),
            'target_quantity' => 12000, 'notes' => 'Monthly extraction target for North Pit.',
        ]);

        // Stock Transfer — a small south-site stockpile of the same product, fed from the north pile
        $southAggregate20Pile = Stockpile::create([
            'company_id' => $companyId, 'extraction_site_id' => $southSite->id, 'material_id' => $aggregate20->id,
            'label' => 'Stockyard B — Bay 2', 'quantity_available' => 5, 'min_level' => 50, 'max_level' => 400, 'unit_value' => 1800,
        ]);

        $transfer = ProductionStockTransfer::create([
            'company_id' => $companyId, 'material_id' => $aggregate20->id, 'quantity' => 40,
            'source_stockpile_id' => $northAggregate20Pile->id, 'destination_stockpile_id' => $southAggregate20Pile->id,
            'vehicle_registration' => 'KD901Q', 'operator_id' => $weighbridgeStaff->id, 'status' => 'Pending',
        ]);
        $transfer->complete();

        // Two full-lifecycle production records: Draft -> Submitted -> Approved,
        // crediting real Stockpile quantities via the WorkflowEngine.
        $engine = app(WorkflowEngine::class);
        $dustPile = Stockpile::withoutGlobalScope('company')->where('extraction_site_id', $northSite->id)
            ->whereHas('material', fn ($q) => $q->where('name', 'like', '%Dust%'))->first();

        foreach ([1, 2] as $i) {
            $log = ProductionLog::create([
                'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'quarry_face_id' => $faces->first()->id,
                'pit_id' => $pits->first()->id, 'production_line_id' => $northLine->id, 'crushing_plant_id' => $northPlant->id,
                'date' => now()->subDays($i), 'material_type' => 'Granite', 'quantity_tonnes' => 420 + ($i * 10),
                'shift' => 'Day', 'logged_by_id' => $crusherOperator->id, 'supervisor_id' => $crusherOperator->id,
                'operator_id' => $crusherOperator->id, 'feed_quantity' => 460, 'crusher_throughput' => 440,
                'finished_product_quantity' => 400, 'rejected_material' => 20, 'waste_material' => 40,
                'by_products' => 10, 'recovery_percent' => 92.5, 'yield_percent' => 87.0,
                'operating_hours' => 10, 'idle_hours' => 1, 'remarks' => 'Standard day-shift production run.',
            ]);

            ProductionOutput::create([
                'company_id' => $companyId, 'production_log_id' => $log->id, 'material_id' => $aggregate20->id,
                'quantity_produced' => 30, 'unit' => 'tonne', 'stockpile_id' => $northAggregate20Pile->id, 'quality_grade' => 'Grade A',
            ]);
            ProductionOutput::create([
                'company_id' => $companyId, 'production_log_id' => $log->id, 'material_id' => $dustPile->material_id,
                'quantity_produced' => 10, 'unit' => 'tonne', 'stockpile_id' => $dustPile->id, 'quality_grade' => 'Standard',
            ]);

            $log->update(['status' => 'Submitted']);
            $request = $log->submitForApproval('production-record-approval', [
                'title' => 'Production Record — ' . $log->production_number,
                'metadata' => ['production_log_id' => $log->id],
            ]);

            $step = $request->steps()->where('status', 'Pending')->first();
            $action = $step?->actions()->whereNull('action')->first();

            if ($action) {
                $engine->approve($action);
            }
        }

        $this->command?->info('ProductionManagementDemoSeeder: faces, pits, production lines, plan, downtime, target, transfer and 2 approved production records seeded.');
    }
}
