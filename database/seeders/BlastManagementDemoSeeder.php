<?php

namespace Database\Seeders;

use App\Models\BlastCompliance;
use App\Models\BlastCost;
use App\Models\BlastDocument;
use App\Models\BlastDrillHole;
use App\Models\BlastEnvironmentalReading;
use App\Models\BlastExecution;
use App\Models\BlastPlan;
use App\Models\BlastSafetyZone;
use App\Models\Equipment;
use App\Models\ExplosiveIssue;
use App\Models\ExtractionSite;
use App\Models\FragmentationAnalysis;
use App\Models\Pit;
use App\Models\PostBlastAnalysis;
use App\Models\QuarryFace;
use App\Models\Staff;
use App\Models\User;
use App\Services\WorkflowEngine;
use Illuminate\Database\Seeder;

/**
 * Extends the Quarry demo company (Rift Valley Quarries Ltd) with real
 * Blast Management data: a Draft blast plan, and two full-lifecycle blast
 * plans taken Draft -> Submitted -> Approved (via the real 3-step
 * WorkflowEngine chain: Safety Officer -> Environmental Officer -> Quarry
 * Manager) -> Executed, each with drill holes, explosive issues, a safety
 * zone, execution details, post-blast + fragmentation analysis,
 * environmental readings, a cost breakdown, a compliance record and a
 * document. Idempotent.
 */
class BlastManagementDemoSeeder extends Seeder
{
    public function run(): void
    {
        $northSite = ExtractionSite::withoutGlobalScope('company')->where('name', 'like', '%North Pit%')->first();

        if (! $northSite) {
            $this->command?->warn('Skipping BlastManagementDemoSeeder: demo quarry site not found (run QuarryDemoSeeder first).');

            return;
        }

        $companyId = $northSite->company_id;

        if (BlastPlan::withoutGlobalScope('company')->where('company_id', $companyId)->exists()) {
            $this->command?->info('BlastManagementDemoSeeder: demo data already exists, skipping.');

            return;
        }

        // Users required to resolve the 3-step Blast Plan Approval chain
        $safetyOfficer = $this->ensureUser($companyId, 'Blast Safety Officer', 'blast.safety.officer@riftvalleyquarries.example', 'Safety Officer');
        $environmentalOfficer = $this->ensureUser($companyId, 'Blast Environmental Officer', 'blast.environmental.officer@riftvalleyquarries.example', 'Environmental Officer');
        $quarryManager = User::withoutGlobalScope('company')->where('email', 'quarry.manager@riftvalleyquarries.example')->first();

        $blastEngineerUser = $this->ensureUser($companyId, 'Blast Engineer User', 'blast.engineer@riftvalleyquarries.example', 'Blast Engineer');

        $blastEngineerStaff = Staff::create([
            'company_id' => $companyId, 'full_name' => 'James Kiptanui', 'phone_number' => '+254712000201',
            'email' => 'james.kiptanui@riftvalleyquarries.example', 'status' => 'Active', 'date_hired' => now()->subYears(4),
        ]);
        $shotFirer = Staff::create([
            'company_id' => $companyId, 'full_name' => 'Peter Mutua', 'phone_number' => '+254712000202',
            'email' => 'peter.mutua@riftvalleyquarries.example', 'status' => 'Active', 'date_hired' => now()->subYears(2),
        ]);

        $pit = Pit::withoutGlobalScope('company')->where('extraction_site_id', $northSite->id)->first();
        $face = QuarryFace::withoutGlobalScope('company')->where('extraction_site_id', $northSite->id)->first();
        $drillRig = Equipment::withoutGlobalScope('company')->where('company_id', $companyId)->where('name', 'like', '%Drill Rig%')->first();

        $engine = app(WorkflowEngine::class);

        // 1. A Draft blast plan — proves the Draft state
        BlastPlan::create([
            'company_id' => $companyId, 'blast_name' => 'North Pit Extension Blast', 'extraction_site_id' => $northSite->id,
            'pit_id' => $pit->id, 'quarry_face_id' => $face->id, 'bench' => 'Bench 3',
            'blast_engineer_id' => $blastEngineerStaff->id, 'date_planned' => now()->addDays(5),
            'priority' => 'Medium', 'drill_pattern' => 'Staggered', 'burden' => 3.0, 'spacing' => 3.5, 'bench_height' => 12,
        ]);

        // 2 & 3. Two full-lifecycle blast plans, taken through the real approval chain to Executed
        foreach ([1, 2] as $i) {
            $plan = BlastPlan::create([
                'company_id' => $companyId, 'blast_name' => "Production Blast #{$i}", 'extraction_site_id' => $northSite->id,
                'pit_id' => $pit->id, 'quarry_face_id' => $face->id, 'bench' => 'Bench ' . $i,
                'blast_engineer_id' => $blastEngineerStaff->id, 'date_planned' => now()->subDays(10 - $i),
                'priority' => 'High', 'status' => 'Draft',
                'drill_pattern' => 'Staggered', 'burden' => 3.2, 'spacing' => 3.8, 'bench_height' => 12, 'sub_drill' => 1.2,
                'scheduled_date' => now()->subDays(10 - $i), 'scheduled_time' => '06:00',
                'safety_officer_id' => $blastEngineerStaff->id,
                'blast_team' => [$blastEngineerStaff->id, $shotFirer->id],
                'security_team' => 'Site Security Unit A', 'emergency_team' => 'Emergency Response Team 1',
            ]);

            foreach (range(1, 6) as $holeNum) {
                BlastDrillHole::create([
                    'company_id' => $companyId, 'blast_plan_id' => $plan->id, 'hole_number' => "H-{$holeNum}",
                    'diameter' => 89, 'depth' => 13.2, 'inclination' => 0, 'drill_rig_id' => $drillRig->id,
                    'operator_id' => $blastEngineerStaff->id, 'start_time' => now()->subDays(10 - $i)->setTime(6, 0),
                    'finish_time' => now()->subDays(10 - $i)->setTime(7, 30),
                ]);
            }

            ExplosiveIssue::create([
                'company_id' => $companyId, 'blast_plan_id' => $plan->id, 'explosive_type' => 'ANFO',
                'batch_number' => "BATCH-{$i}", 'supplier' => 'Kenya Explosives Ltd', 'storage_magazine' => 'Magazine A',
                'quantity_issued' => 600, 'quantity_used' => 560, 'detonator_type' => 'Electronic',
                'initiation_system' => 'Non-Electric', 'issue_date' => now()->subDays(10 - $i),
                'expiry_date' => now()->addYear(), 'approval_status' => 'Approved',
            ]);

            BlastSafetyZone::create([
                'company_id' => $companyId, 'blast_plan_id' => $plan->id, 'safety_radius' => 500,
                'restricted_areas' => 'North access road, haul road junction', 'evacuation_routes' => 'Route A via South gate',
                'assembly_points' => 'Assembly Point 1 (Main Office car park)', 'warning_sirens' => true,
                'road_closures' => 'North haul road closed 05:45–06:30', 'access_control' => 'Security checkpoint at North gate',
                'clearance_confirmed' => true, 'clearance_confirmed_by_id' => $blastEngineerStaff->id,
            ]);

            // Submit for approval and take through the full 3-step chain
            $plan->update(['status' => 'Submitted']);
            $request = $plan->submitForApproval('blast-plan-approval', [
                'title' => 'Blast Plan — ' . $plan->blast_number,
                'metadata' => ['blast_plan_id' => $plan->id],
            ]);

            $this->approveNextStep($engine, $request, $safetyOfficer);
            $this->approveNextStep($engine, $request->fresh(), $environmentalOfficer);
            $this->approveNextStep($engine, $request->fresh(), $quarryManager);

            $plan->update(['status' => 'Executed']);

            BlastExecution::create([
                'company_id' => $companyId, 'blast_plan_id' => $plan->id, 'actual_date' => now()->subDays(10 - $i),
                'actual_time' => '06:15', 'responsible_engineer_id' => $blastEngineerStaff->id, 'shot_firer_id' => $shotFirer->id,
                'weather_conditions' => 'Clear, light wind', 'explosives_used' => 560, 'detonators_used' => 6,
                'initiation_sequence' => 'Sequential delay, 25ms interval', 'blast_duration_seconds' => 8,
                'blast_outcome' => $i === 1 ? 'Successful' : 'Partial',
            ]);

            PostBlastAnalysis::create([
                'company_id' => $companyId, 'blast_plan_id' => $plan->id, 'rock_fragmentation' => 'Good, uniform',
                'oversized_rocks' => 6.5, 'fly_rock' => false, 'back_break' => 0.8, 'toe_condition' => 'Clean',
                'bench_stability' => 'Stable', 'diggability' => 'Excellent', 'production_readiness' => 'Ready',
                'recommendations' => 'Maintain current burden/spacing for next blast.',
            ]);

            FragmentationAnalysis::create([
                'company_id' => $companyId, 'blast_plan_id' => $plan->id, 'average_fragment_size' => 22 + $i,
                'maximum_fragment' => 65, 'minimum_fragment' => 3, 'oversized_percentage' => 6.5,
                'fines_percentage' => 12.0, 'crusher_feed_suitability' => 'Suitable',
            ]);

            BlastEnvironmentalReading::create([
                'company_id' => $companyId, 'blast_plan_id' => $plan->id, 'ground_vibration' => 8.5,
                'air_overpressure' => 110, 'dust_level' => 45, 'noise_level' => 95,
                'weather_conditions' => 'Clear', 'monitoring_equipment' => 'Instantel Minimate Pro',
                'compliance_status' => 'Compliant', 'recorded_at' => now()->subDays(10 - $i)->setTime(6, 20),
            ]);

            BlastCost::create([
                'company_id' => $companyId, 'blast_plan_id' => $plan->id, 'drilling_cost' => 45000,
                'explosive_cost' => 120000, 'labour_cost' => 25000, 'equipment_cost' => 18000,
                'fuel_cost' => 8000, 'security_cost' => 5000, 'environmental_monitoring_cost' => 4000,
            ]);

            BlastCompliance::create([
                'company_id' => $companyId, 'blast_plan_id' => $plan->id, 'permit_number' => "DOSH-QRY-{$i}-2026",
                'permit_expiry' => now()->addMonths(6), 'regulatory_approval' => true, 'blast_notifications_sent' => true,
                'inspector_approval' => true, 'safety_checklist_complete' => true,
                'environmental_checklist_complete' => true, 'legal_compliance' => true,
            ]);

            BlastDocument::create([
                'company_id' => $companyId, 'blast_plan_id' => $plan->id, 'category' => 'Blast Design',
                'file_path' => 'blast-documents/demo-blast-design.txt', 'uploaded_by_id' => $blastEngineerUser->id,
            ]);
        }

        $this->command?->info('BlastManagementDemoSeeder: 1 draft + 2 full-lifecycle (Executed) blast plans seeded with all child records.');
    }

    protected function ensureUser(int $companyId, string $name, string $email, string $role): User
    {
        $user = User::withoutGlobalScope('company')->where('email', $email)->first();

        if (! $user) {
            $user = User::create(['company_id' => $companyId, 'name' => $name, 'email' => $email, 'password' => 'password', 'email_verified_at' => now()]);
            $user->assignRole($role);
        }

        return $user;
    }

    protected function approveNextStep(WorkflowEngine $engine, $request, User $approver): void
    {
        $step = $request->steps()->where('status', 'Pending')->first();
        $action = $step?->actions()->where('approver_id', $approver->id)->whereNull('action')->first();

        if ($action) {
            $engine->approve($action);
        }
    }
}
