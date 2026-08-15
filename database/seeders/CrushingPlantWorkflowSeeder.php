<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ProductionRun;
use App\Models\WorkflowDefinition;
use Illuminate\Database\Seeder;

/**
 * Seeds the "Production Run Approval" WorkflowDefinition for every real
 * Quarry company — the 5th flagship Workflow & Approvals integration
 * (alongside Leave Request, Purchase Request, Production Record, and Blast
 * Plan Approval), single-approver design matching ProductionWorkflowSeeder's
 * exact pattern. Idempotent.
 */
class CrushingPlantWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->where('industry', 'quarry')->get();

        if ($companies->isEmpty()) {
            $this->command?->warn('Skipping CrushingPlantWorkflowSeeder: no Quarry companies found.');

            return;
        }

        foreach ($companies as $company) {
            if (WorkflowDefinition::withoutGlobalScope('company')->where('company_id', $company->id)->where('internal_key', 'production-run-approval')->exists()) {
                continue;
            }

            WorkflowDefinition::create([
                'company_id' => $company->id,
                'name' => 'Production Run Approval',
                'internal_key' => 'production-run-approval',
                'description' => 'Every submitted crushing plant production run is reviewed by a Plant Manager before it credits stockpiles. Escalates automatically if not actioned within 24 hours.',
                'module' => 'Crushing Plant Management',
                'entity_type' => ProductionRun::class,
                'status' => 'Published',
                'version' => 1,
                'definition' => [
                    'nodes' => [
                        ['id' => '1', 'type' => 'start', 'position' => ['x' => 60, 'y' => 160], 'config' => []],
                        ['id' => '2', 'type' => 'approval_step', 'position' => ['x' => 320, 'y' => 160], 'config' => [
                            'approver_type' => 'role', 'role' => 'Plant Manager',
                            'approver_resolution_mode' => 'any_one', 'escalation_hours' => 24,
                        ]],
                        ['id' => '3', 'type' => 'end', 'position' => ['x' => 580, 'y' => 160], 'config' => []],
                    ],
                    'edges' => [
                        ['from' => '1', 'to' => '2'],
                        ['from' => '2', 'to' => '3'],
                    ],
                ],
                'node_types_used' => ['start', 'approval_step', 'end'],
            ]);

            $this->command?->info("CrushingPlantWorkflowSeeder: Production Run Approval seeded for company {$company->id} ({$company->name}).");
        }
    }
}
