<?php

namespace Database\Seeders;

use App\Models\Calibration;
use App\Models\Certificate;
use App\Models\Client;
use App\Models\CrushingPlant;
use App\Models\CustomerQualityRequirement;
use App\Models\DispatchOrder;
use App\Models\ExtractionSite;
use App\Models\LabEquipment;
use App\Models\LabSample;
use App\Models\LabTest;
use App\Models\Material;
use App\Models\NonConformance;
use App\Models\QualityCorrectiveAction;
use App\Models\QualityStandard;
use App\Models\SamplingRequest;
use App\Models\Staff;
use App\Models\Stockpile;
use App\Models\TestMethod;
use App\Models\User;
use App\Services\Quarry\QualityHoldService;
use Illuminate\Database\Seeder;

/**
 * Extends the Quarry demo company (Rift Valley Quarries Ltd) with real
 * Laboratory & Quality Control data: 7 quality standards, ~10 test methods,
 * 2 lab equipment items (one with overdue calibration to demonstrate the
 * LabTestResource block) + calibration history, 2 sampling requests, 3 lab
 * samples taken through the full lifecycle (1 clean pass -> certificate,
 * 1 failure -> NCR -> CAPA, 1 demonstrating a full QualityHold ->
 * DispatchOrder-blocked -> release -> dispatch-auto-approves cycle), a
 * customer quality requirement, and Laboratory Manager/Quality Manager
 * demo users so the approval queue isn't a dead end. Idempotent.
 */
class LaboratoryQualityControlDemoSeeder extends Seeder
{
    public function run(): void
    {
        $northSite = ExtractionSite::withoutGlobalScope('company')->where('name', 'like', '%North Pit%')->first();

        if (! $northSite) {
            $this->command?->warn('Skipping LaboratoryQualityControlDemoSeeder: demo quarry site not found (run QuarryDemoSeeder first).');

            return;
        }

        $companyId = $northSite->company_id;

        if (QualityStandard::withoutGlobalScope('company')->where('company_id', $companyId)->exists()) {
            $this->command?->info('LaboratoryQualityControlDemoSeeder: demo data already exists, skipping.');

            return;
        }

        $crushingPlant = CrushingPlant::withoutGlobalScope('company')->where('company_id', $companyId)->first();
        $stockpile = Stockpile::withoutGlobalScope('company')->where('company_id', $companyId)->first();
        $material = Material::withoutGlobalScope('company')->find($stockpile->material_id);
        $staff = Staff::withoutGlobalScope('company')->where('company_id', $companyId)->where('full_name', 'John Mwangi')->first();
        $client = Client::withoutGlobalScope('company')->where('company_id', $companyId)->first();

        if (! $crushingPlant || ! $stockpile || ! $material || ! $client) {
            $this->command?->warn('Skipping LaboratoryQualityControlDemoSeeder: prerequisite demo records not found.');

            return;
        }

        // Demo users so the approval queue and equipment ownership aren't dead ends
        if (! User::withoutGlobalScope('company')->where('email', 'lab.manager@riftvalleyquarries.example')->exists()) {
            $labManagerUser = User::create([
                'company_id' => $companyId, 'name' => 'Esther Nyambura',
                'email' => 'lab.manager@riftvalleyquarries.example', 'password' => 'password', 'email_verified_at' => now(),
            ]);
            $labManagerUser->assignRole('Laboratory Manager');
        }

        if (! User::withoutGlobalScope('company')->where('email', 'quality.manager@riftvalleyquarries.example')->exists()) {
            $qualityManagerUser = User::create([
                'company_id' => $companyId, 'name' => 'Patrick Omondi',
                'email' => 'quality.manager@riftvalleyquarries.example', 'password' => 'password', 'email_verified_at' => now(),
            ]);
            $qualityManagerUser->assignRole('Quality Manager');
        }

        $labTechnician = Staff::withoutGlobalScope('company')->where('company_id', $companyId)->where('full_name', 'John Mwangi')->first();

        // --- Quality Standards ---
        $standards = collect([
            ['standard_code' => 'KEBS', 'name' => 'Kenya Bureau of Standards', 'issuing_body' => 'KEBS'],
            ['standard_code' => 'ASTM', 'name' => 'American Society for Testing and Materials', 'issuing_body' => 'ASTM International'],
            ['standard_code' => 'ISO', 'name' => 'International Organization for Standardization', 'issuing_body' => 'ISO'],
            ['standard_code' => 'BS EN', 'name' => 'British Standard EN', 'issuing_body' => 'BSI'],
            ['standard_code' => 'AASHTO', 'name' => 'American Association of State Highway and Transportation Officials', 'issuing_body' => 'AASHTO'],
            ['standard_code' => 'CUSTOMER-SPEC', 'name' => 'Customer Specification', 'issuing_body' => 'Customer'],
            ['standard_code' => 'GOVT-SPEC', 'name' => 'Government Specification', 'issuing_body' => 'Government of Kenya'],
        ])->map(fn ($attrs) => QualityStandard::create(array_merge(['company_id' => $companyId], $attrs)));

        $kebs = $standards->firstWhere('standard_code', 'KEBS');
        $astm = $standards->firstWhere('standard_code', 'ASTM');

        // --- Test Methods (spec's example test list) ---
        $methodNames = [
            'Particle Size Distribution', 'Sieve Analysis', 'Flakiness Index', 'Elongation Index',
            'Los Angeles Abrasion', 'Aggregate Crushing Value', 'Aggregate Impact Value', 'Water Absorption',
            'Moisture Content', 'Specific Gravity', 'Bulk Density', 'Compaction',
            'Dust Content', 'Clay Lumps', 'Organic Impurities', 'Soundness', 'Shape Index',
        ];
        $methods = collect($methodNames)->map(fn ($name, $i) => TestMethod::create([
            'company_id' => $companyId, 'method_name' => $name,
            'quality_standard_id' => $i % 2 === 0 ? $kebs->id : $astm->id,
            'testing_steps' => "Standard procedure for {$name} per the assigned quality standard.",
            'status' => 'Active',
        ]));

        $moistureMethod = $methods->firstWhere('method_name', 'Moisture Content');
        $dustMethod = $methods->firstWhere('method_name', 'Dust Content');

        // --- Laboratory Equipment + Calibration ---
        $ovenEquipment = LabEquipment::create([
            'company_id' => $companyId, 'name' => 'Moisture Oven MO-1', 'manufacturer' => 'Thermo Scientific',
            'model' => 'Heratherm', 'serial_number' => 'TS-2024-001', 'responsible_technician_id' => $labTechnician?->id,
            'calibration_due_date' => now()->addMonths(6), 'status' => 'Operational',
        ]);
        Calibration::create([
            'company_id' => $companyId, 'lab_equipment_id' => $ovenEquipment->id, 'calibration_date' => now()->subMonths(6),
            'due_date' => now()->addMonths(6), 'performed_by_id' => $labTechnician?->id, 'status' => 'Valid',
        ]);

        // Deliberately overdue, to demonstrate LabTestResource's real block
        LabEquipment::create([
            'company_id' => $companyId, 'name' => 'Sieve Shaker SS-1', 'manufacturer' => 'Retsch',
            'model' => 'AS 200', 'serial_number' => 'RT-2022-014', 'responsible_technician_id' => $labTechnician?->id,
            'calibration_due_date' => now()->subDays(10), 'status' => 'Due Calibration',
        ]);

        // --- Sampling Requests (manual trigger reasons) ---
        $request1 = SamplingRequest::create([
            'company_id' => $companyId, 'trigger' => 'Routine Inspection', 'priority' => 'Normal',
            'requested_by_id' => $labTechnician?->id, 'assigned_technician_id' => $labTechnician?->id,
            'due_date' => now()->addDay(), 'status' => 'In Progress',
        ]);
        SamplingRequest::create([
            'company_id' => $companyId, 'trigger' => 'Customer Order', 'priority' => 'High',
            'requested_by_id' => $labTechnician?->id, 'due_date' => now()->addDays(2), 'status' => 'Pending',
        ]);

        // --- Sample 1: clean pass -> quality approval -> certificate ---
        $sample1 = LabSample::create([
            'company_id' => $companyId, 'sample_type' => 'Finished Product', 'extraction_site_id' => $northSite->id,
            'crushing_plant_id' => $crushingPlant->id, 'stockpile_id' => $stockpile->id, 'material_id' => $material->id,
            'product_grade' => 'Grade A', 'batch_number' => 'BATCH-2026-001', 'sampling_request_id' => $request1->id,
            'collected_by_id' => $labTechnician?->id, 'collection_date' => now()->subDay(),
            'collection_location' => 'Stockyard A', 'status' => 'Received',
        ]);
        $test1 = LabTest::create([
            'company_id' => $companyId, 'lab_sample_id' => $sample1->id, 'test_method_id' => $moistureMethod->id,
            'technician_id' => $labTechnician?->id, 'lab_equipment_id' => $ovenEquipment->id,
            'start_time' => now()->subDay(), 'measured_values' => ['moisture_percent' => 3.8],
            'expected_values' => ['moisture_percent' => 4.0], 'tolerance' => 0.5,
        ]);
        $test1->complete();
        $sample1->update(['status' => 'Approved']);
        $certificate1 = Certificate::create([
            'company_id' => $companyId, 'type' => 'Certificate of Analysis', 'lab_sample_id' => $sample1->id,
            'customer_id' => $client->id, 'material_id' => $material->id, 'batch_number' => $sample1->batch_number,
        ]);
        $certificate1->issue($labTechnician?->id);

        // --- Sample 2: failure -> NCR -> CAPA ---
        $sample2 = LabSample::create([
            'company_id' => $companyId, 'sample_type' => 'Stockpile', 'extraction_site_id' => $northSite->id,
            'crushing_plant_id' => $crushingPlant->id, 'stockpile_id' => $stockpile->id, 'material_id' => $material->id,
            'product_grade' => 'Grade A', 'batch_number' => 'BATCH-2026-002',
            'collected_by_id' => $labTechnician?->id, 'collection_date' => now()->subDays(2), 'status' => 'Received',
        ]);
        $test2 = LabTest::create([
            'company_id' => $companyId, 'lab_sample_id' => $sample2->id, 'test_method_id' => $dustMethod->id,
            'technician_id' => $labTechnician?->id, 'start_time' => now()->subDays(2),
            'measured_values' => ['dust_percent' => 9.5], 'expected_values' => ['dust_percent' => 5.0], 'tolerance' => 1.0,
        ]);
        $test2->complete();
        $sample2->update(['status' => 'Rejected']);

        $ncr = NonConformance::create([
            'company_id' => $companyId, 'source' => 'Lab Test', 'lab_sample_id' => $sample2->id,
            'description' => 'Dust content of ' . $test2->measured_values['dust_percent'] . '% exceeded the acceptance criteria of ' . $test2->expected_values['dust_percent'] . '%.',
            'severity' => 'Major', 'root_cause' => 'Insufficient washing during crushing.',
            'immediate_action' => 'Stockpile placed on quality hold pending corrective action.',
            'responsible_person_id' => $labTechnician?->id, 'due_date' => now()->addWeek(),
        ]);
        QualityCorrectiveAction::create([
            'company_id' => $companyId, 'non_conformance_id' => $ncr->id, 'responsible_person_id' => $labTechnician?->id,
            'due_date' => now()->addWeek(), 'progress_percent' => 20, 'status' => 'In Progress',
        ]);

        // --- Sample 3: demonstrates the full QualityHold -> DispatchOrder-blocked -> release cycle ---
        $sample3 = LabSample::create([
            'company_id' => $companyId, 'sample_type' => 'Stockpile', 'extraction_site_id' => $northSite->id,
            'stockpile_id' => $stockpile->id, 'material_id' => $material->id, 'batch_number' => 'BATCH-2026-003',
            'collected_by_id' => $labTechnician?->id, 'collection_date' => now(), 'status' => 'Testing',
        ]);

        $holdService = app(QualityHoldService::class);
        $hold = $holdService->place($stockpile, 'Pending Test', $labTechnician?->id, $sample3->id, 'Awaiting retest before release.');

        // A dispatch order against this now-held stockpile is blocked, exactly like a real dispatcher would hit
        $blockedOrder = DispatchOrder::create([
            'company_id' => $companyId, 'stockpile_id' => $stockpile->id, 'material_id' => $material->id,
            'customer_id' => $client->id, 'vehicle_registration' => 'KDQ 900L', 'net_weight' => 25,
            'weight_status' => 'Within Tolerance', 'status' => 'Gross Weighed',
        ]);
        $blockedOrder->runDecisionEngine();

        // Retest passes -> release the hold -> a fresh dispatch now auto-approves normally
        $sample3->update(['status' => 'Approved']);
        $holdService->release($hold, 'Retesting', $labTechnician?->id, 'Retested — within acceptance criteria.');

        $clearedOrder = DispatchOrder::create([
            'company_id' => $companyId, 'stockpile_id' => $stockpile->id, 'material_id' => $material->id,
            'customer_id' => $client->id, 'vehicle_registration' => 'KDQ 901M', 'net_weight' => 25,
            'weight_status' => 'Within Tolerance', 'status' => 'Gross Weighed',
        ]);
        $clearedOrder->runDecisionEngine();

        // --- Customer Quality Requirement ---
        CustomerQualityRequirement::create([
            'company_id' => $companyId, 'customer_id' => $client->id, 'material_id' => $material->id,
            'max_dust_percent' => 5.0, 'moisture_limit' => 4.5, 'status' => 'Active',
        ]);

        $this->command?->info('LaboratoryQualityControlDemoSeeder: 7 quality standards, 17 test methods, 2 lab equipment items (1 overdue calibration), 2 sampling requests, 3 lab samples (1 pass+certificate, 1 fail+NCR+CAPA, 1 demonstrating the full quality-hold dispatch-block/release cycle), and a customer quality requirement seeded.');
    }
}
