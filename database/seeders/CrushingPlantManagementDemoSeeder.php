<?php

namespace Database\Seeders;

use App\Models\Conveyor;
use App\Models\Crusher;
use App\Models\CrushingPlant;
use App\Models\EnergyReading;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\ExtractionSite;
use App\Models\Feeder;
use App\Models\Material;
use App\Models\PlantDocument;
use App\Models\PlantOperation;
use App\Models\PlantQualityTest;
use App\Models\ProductionDowntime;
use App\Models\ProductionLine;
use App\Models\ProductionOutput;
use App\Models\ProductionRun;
use App\Models\Screen;
use App\Models\Staff;
use App\Models\Stockpile;
use App\Models\User;
use App\Services\WorkflowEngine;
use Illuminate\Database\Seeder;

/**
 * Extends the Quarry demo company (Rift Valley Quarries Ltd) with real
 * Crushing Plant Management data: enriches the existing demo CrushingPlant/
 * ProductionLine records with the new fields, adds crushers/screens/
 * conveyors/feeders, plant operations, downtime, energy readings, quality
 * tests, a document, and two full-lifecycle production runs taken all the
 * way through Draft -> Submitted -> Approved via the real WorkflowEngine so
 * Stockpile levels genuinely increase. Idempotent.
 */
class CrushingPlantManagementDemoSeeder extends Seeder
{
    public function run(): void
    {
        $northSite = ExtractionSite::withoutGlobalScope('company')->where('name', 'like', '%North Pit%')->first();
        $southSite = ExtractionSite::withoutGlobalScope('company')->where('name', 'like', '%South Pit%')->first();

        if (! $northSite || ! $southSite) {
            $this->command?->warn('Skipping CrushingPlantManagementDemoSeeder: demo quarry sites not found (run QuarryDemoSeeder first).');

            return;
        }

        $companyId = $northSite->company_id;

        $northPlant = CrushingPlant::withoutGlobalScope('company')->where('extraction_site_id', $northSite->id)->first();
        $southPlant = CrushingPlant::withoutGlobalScope('company')->where('extraction_site_id', $southSite->id)->first();
        $northLine = ProductionLine::withoutGlobalScope('company')->where('crushing_plant_id', $northPlant?->id)->first();
        $southLine = ProductionLine::withoutGlobalScope('company')->where('crushing_plant_id', $southPlant?->id)->first();

        if (! $northPlant || ! $southPlant || ! $northLine || ! $southLine) {
            $this->command?->warn('Skipping CrushingPlantManagementDemoSeeder: demo plants/lines not found (run ProductionManagementDemoSeeder first).');

            return;
        }

        if (Crusher::withoutGlobalScope('company')->where('company_id', $companyId)->exists()) {
            $this->command?->info('CrushingPlantManagementDemoSeeder: demo data already exists, skipping.');

            return;
        }

        if (! User::withoutGlobalScope('company')->where('email', 'plant.manager@riftvalleyquarries.example')->exists()) {
            $plantManagerUser = User::create([
                'company_id' => $companyId, 'name' => 'Samuel Kimani',
                'email' => 'plant.manager@riftvalleyquarries.example', 'password' => 'password', 'email_verified_at' => now(),
            ]);
            $plantManagerUser->assignRole('Plant Manager');
        }

        $crusherOperator = Staff::withoutGlobalScope('company')->where('company_id', $companyId)->where('full_name', 'John Mwangi')->first();
        $aggregate20 = Material::withoutGlobalScope('company')->where('company_id', $companyId)->where('name', 'like', '%20mm%')->first();
        $northAggregate20Pile = Stockpile::withoutGlobalScope('company')->where('extraction_site_id', $northSite->id)->where('material_id', $aggregate20->id)->first();
        $plantManagerUserRecord = User::withoutGlobalScope('company')->where('email', 'plant.manager@riftvalleyquarries.example')->first();

        // Enrich the existing demo Plants and Production Lines with the new fields
        $northPlant->update([
            'plant_code' => 'PLT-0001', 'location' => 'North Pit — Primary Crushing Bay',
            'commission_date' => now()->subYears(5), 'manufacturer' => 'Sandvik', 'model' => 'CJ412',
            'serial_number' => 'SNV-CJ412-8841', 'supervisor_id' => $crusherOperator?->id,
        ]);
        $southPlant->update([
            'plant_code' => 'PLT-0002', 'location' => 'South Pit — Secondary Crushing Bay',
            'commission_date' => now()->subYears(3), 'manufacturer' => 'Metso', 'model' => 'NW106',
            'serial_number' => 'MET-NW106-2273', 'supervisor_id' => $crusherOperator?->id,
        ]);
        $northLine->update(['line_code' => 'LINE-0001', 'current_capacity_tph' => 235, 'supervisor_id' => $crusherOperator?->id]);
        $southLine->update(['line_code' => 'LINE-0002', 'current_capacity_tph' => 140, 'supervisor_id' => $crusherOperator?->id]);

        // Process-flow equipment — one of each stage per line
        // Each Crusher is also a physical asset — link it back to an
        // Equipment record so Equipment::crusher() (and EAM reporting that
        // groups equipment by category/site) doesn't see every crusher as
        // an orphaned, uncategorized, siteless asset.
        $crusherCategory = EquipmentCategory::create(['company_id' => $companyId, 'name' => 'Crushers', 'code' => 'CRUSHER']);

        $northCrusherEquipment = Equipment::create([
            'company_id' => $companyId, 'name' => 'Primary Jaw Crusher — North Pit', 'equipment_type' => 'Crusher',
            'status' => 'Operational', 'equipment_category_id' => $crusherCategory->id, 'extraction_site_id' => $northSite->id,
        ]);
        $southCrusherEquipment = Equipment::create([
            'company_id' => $companyId, 'name' => 'Secondary Cone Crusher — South Pit', 'equipment_type' => 'Crusher',
            'status' => 'Idle', 'equipment_category_id' => $crusherCategory->id, 'extraction_site_id' => $southSite->id,
        ]);

        $northCrusher = Crusher::create([
            'company_id' => $companyId, 'production_line_id' => $northLine->id, 'type' => 'Primary Jaw Crusher',
            'manufacturer' => 'Sandvik', 'model' => 'CJ412', 'capacity_tph' => 250, 'current_throughput' => 235,
            'operating_hours' => 8600, 'wear_status' => 'Good', 'status' => 'Running', 'equipment_id' => $northCrusherEquipment->id,
        ]);
        Crusher::create([
            'company_id' => $companyId, 'production_line_id' => $southLine->id, 'type' => 'Secondary Cone Crusher',
            'manufacturer' => 'Metso', 'model' => 'NW106', 'capacity_tph' => 150, 'current_throughput' => 0,
            'operating_hours' => 5400, 'wear_status' => 'Monitor', 'status' => 'Idle', 'equipment_id' => $southCrusherEquipment->id,
        ]);

        Screen::create([
            'company_id' => $companyId, 'production_line_id' => $northLine->id, 'name' => 'North Vibrating Screen 1',
            'decks' => 3, 'mesh_sizes' => '40mm/20mm/10mm', 'capacity_tph' => 240, 'current_output' => 230,
            'operating_hours' => 8100, 'status' => 'Running',
        ]);
        Screen::create([
            'company_id' => $companyId, 'production_line_id' => $southLine->id, 'name' => 'South Vibrating Screen 1',
            'decks' => 2, 'mesh_sizes' => '20mm/10mm', 'capacity_tph' => 150, 'current_output' => 0,
            'operating_hours' => 5000, 'status' => 'Idle',
        ]);

        Conveyor::create([
            'company_id' => $companyId, 'production_line_id' => $northLine->id, 'name' => 'North Conveyor CV-1',
            'length_m' => 45, 'capacity_tph' => 250, 'motor_power_kw' => 37, 'operating_hours' => 8600,
            'speed_mps' => 2.1, 'status' => 'Running',
        ]);
        Conveyor::create([
            'company_id' => $companyId, 'production_line_id' => $southLine->id, 'name' => 'South Conveyor CV-1',
            'length_m' => 30, 'capacity_tph' => 150, 'motor_power_kw' => 22, 'operating_hours' => 5400,
            'speed_mps' => 1.8, 'status' => 'Idle',
        ]);

        Feeder::create([
            'company_id' => $companyId, 'production_line_id' => $northLine->id, 'name' => 'North Vibrating Feeder',
            'type' => 'Vibrating', 'capacity_tph' => 260, 'feed_rate' => 240, 'status' => 'Running',
        ]);
        Feeder::create([
            'company_id' => $companyId, 'production_line_id' => $southLine->id, 'name' => 'South Apron Feeder',
            'type' => 'Apron', 'capacity_tph' => 160, 'feed_rate' => 0, 'status' => 'Idle',
        ]);

        // Plant Operations — 3 days of shift logs, both plants
        foreach ([2, 1, 0] as $daysAgo) {
            PlantOperation::create([
                'company_id' => $companyId, 'crushing_plant_id' => $northPlant->id, 'production_line_id' => $northLine->id,
                'shift' => 'Day', 'operator_id' => $crusherOperator?->id, 'material_id' => $aggregate20->id,
                'feed_quantity' => 450, 'start_time' => now()->subDays($daysAgo)->setTime(6, 0),
                'end_time' => now()->subDays($daysAgo)->setTime(18, 0), 'downtime_minutes' => 30,
                'weather' => 'Clear', 'remarks' => 'Normal day-shift operation.',
            ]);
        }

        // Downtime — linked to real equipment
        ProductionDowntime::create([
            'company_id' => $companyId, 'crushing_plant_id' => $northPlant->id, 'production_line_id' => $northLine->id,
            'crusher_id' => $northCrusher->id, 'reason' => 'Crusher Jaw Liner Wear', 'approval_status' => 'Approved',
            'start_time' => now()->subDays(1)->setTime(14, 0), 'end_time' => now()->subDays(1)->setTime(14, 45),
            'duration_minutes' => 45, 'root_cause' => 'Jaw liner reaching wear limit', 'corrective_action' => 'Liner inspected and scheduled for replacement',
            'reported_by_id' => $crusherOperator?->id,
        ]);

        // Energy readings — 3 days, both plants
        foreach ([2, 1, 0] as $daysAgo) {
            EnergyReading::create([
                'company_id' => $companyId, 'crushing_plant_id' => $northPlant->id, 'date' => now()->subDays($daysAgo),
                'electricity_kwh' => rand(2800, 3200), 'diesel_litres' => rand(80, 120), 'energy_cost' => rand(45000, 55000),
                'peak_usage_kw' => rand(320, 380), 'power_factor' => 0.92,
            ]);
        }
        EnergyReading::create([
            'company_id' => $companyId, 'crushing_plant_id' => $southPlant->id, 'date' => now(),
            'electricity_kwh' => 900, 'diesel_litres' => 20, 'energy_cost' => 15000, 'peak_usage_kw' => 110, 'power_factor' => 0.88,
        ]);

        // Document
        PlantDocument::create([
            'company_id' => $companyId, 'crushing_plant_id' => $northPlant->id, 'category' => 'Inspection Certificate',
            'file_path' => 'plant-documents/demo-inspection-certificate.txt', 'uploaded_by_id' => $plantManagerUserRecord?->id,
        ]);

        // Two full-lifecycle production runs: Draft -> Submitted -> Approved,
        // crediting real Stockpile quantities via the WorkflowEngine.
        $engine = app(WorkflowEngine::class);

        foreach ([1, 2] as $i) {
            $run = ProductionRun::create([
                'company_id' => $companyId, 'crushing_plant_id' => $northPlant->id, 'production_line_id' => $northLine->id,
                'material_id' => $aggregate20->id, 'input_quantity' => 460, 'output_quantity' => 400 + ($i * 5),
                'start_time' => now()->subDays(3 - $i)->setTime(6, 0), 'finish_time' => now()->subDays(3 - $i)->setTime(18, 0),
                'supervisor_id' => $crusherOperator?->id, 'status' => 'Draft',
            ]);

            ProductionOutput::create([
                'company_id' => $companyId, 'production_run_id' => $run->id, 'material_id' => $aggregate20->id,
                'quantity_produced' => 400 + ($i * 5), 'unit' => 'tonne', 'stockpile_id' => $northAggregate20Pile->id, 'quality_grade' => 'Grade A',
            ]);

            PlantQualityTest::create([
                'company_id' => $companyId, 'crushing_plant_id' => $northPlant->id, 'production_run_id' => $run->id,
                'material_id' => $aggregate20->id, 'particle_size_distribution' => '20mm / 95% passing',
                'moisture_percent' => 2.8, 'density' => 1.65, 'pass_fail' => true, 'approved_for_dispatch' => true,
                'tested_by_id' => $crusherOperator?->id,
            ]);

            $run->update(['status' => 'Submitted']);
            $request = $run->submitForApproval('production-run-approval', [
                'title' => 'Production Run — ' . $run->run_number,
                'metadata' => ['production_run_id' => $run->id],
            ]);

            $step = $request->steps()->where('status', 'Pending')->first();
            $action = $step?->actions()->whereNull('action')->first();

            if ($action) {
                $engine->approve($action);
            }
        }

        $this->command?->info('CrushingPlantManagementDemoSeeder: plants/lines enriched, crushers/screens/conveyors/feeders, plant operations, downtime, energy readings, document and 2 approved production runs seeded.');
    }
}
