<?php

namespace Database\Seeders;

use App\Models\BlastPlan;
use App\Models\Company;
use App\Models\WorkflowDefinition;
use Illuminate\Database\Seeder;

/**
 * Seeds the "Blast Plan Approval" WorkflowDefinition for every real Quarry
 * company — the 4th flagship Workflow & Approvals integration (after Leave
 * Request, Purchase Request, Production Record). A genuine 3-step
 * sequential approval: Safety Officer -> Environmental Officer -> Quarry
 * Manager, matching the exact multi-step node shape proven in
 * WorkflowFlagshipSeeder's Purchase Request Approval. Idempotent.
 */
class BlastWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->where('industry', 'quarry')->get();

        if ($companies->isEmpty()) {
            $this->command?->warn('Skipping BlastWorkflowSeeder: no Quarry companies found.');

            return;
        }

        foreach ($companies as $company) {
            if (WorkflowDefinition::withoutGlobalScope('company')->where('company_id', $company->id)->where('internal_key', 'blast-plan-approval')->exists()) {
                continue;
            }

            WorkflowDefinition::create([
                'company_id' => $company->id,
                'name' => 'Blast Plan Approval',
                'internal_key' => 'blast-plan-approval',
                'description' => 'Every submitted blast plan is reviewed by the Safety Officer, then the Environmental Officer, then the Quarry Manager before it can be executed. Escalates automatically if not actioned within the configured window.',
                'module' => 'Blast Management',
                'entity_type' => BlastPlan::class,
                'status' => 'Published',
                'version' => 1,
                'definition' => [
                    'nodes' => [
                        ['id' => '1', 'type' => 'start', 'position' => ['x' => 40, 'y' => 160], 'config' => []],
                        ['id' => '2', 'type' => 'approval_step', 'position' => ['x' => 280, 'y' => 160], 'config' => [
                            'approver_type' => 'role', 'role' => 'Safety Officer',
                            'approver_resolution_mode' => 'any_one', 'escalation_hours' => 12,
                        ]],
                        ['id' => '3', 'type' => 'approval_step', 'position' => ['x' => 520, 'y' => 160], 'config' => [
                            'approver_type' => 'role', 'role' => 'Environmental Officer',
                            'approver_resolution_mode' => 'any_one', 'escalation_hours' => 12,
                        ]],
                        ['id' => '4', 'type' => 'approval_step', 'position' => ['x' => 760, 'y' => 160], 'config' => [
                            'approver_type' => 'role', 'role' => 'Quarry Manager',
                            'approver_resolution_mode' => 'any_one', 'escalation_hours' => 24,
                        ]],
                        ['id' => '5', 'type' => 'end', 'position' => ['x' => 1000, 'y' => 160], 'config' => []],
                    ],
                    'edges' => [
                        ['from' => '1', 'to' => '2'],
                        ['from' => '2', 'to' => '3'],
                        ['from' => '3', 'to' => '4'],
                        ['from' => '4', 'to' => '5'],
                    ],
                ],
                'node_types_used' => ['start', 'approval_step', 'end'],
            ]);

            $this->command?->info("BlastWorkflowSeeder: Blast Plan Approval seeded for company {$company->id} ({$company->name}).");
        }
    }
}
