<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ProductionLog;
use App\Models\WorkflowDefinition;
use Illuminate\Database\Seeder;

/**
 * Seeds the "Production Record Approval" WorkflowDefinition for every real
 * Quarry company — the 3rd flagship Workflow & Approvals integration
 * (alongside Leave Request Approval and Purchase Request Approval),
 * matching WorkflowFlagshipSeeder's exact pattern. Idempotent.
 */
class ProductionWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->where('industry', 'quarry')->get();

        if ($companies->isEmpty()) {
            $this->command?->warn('Skipping ProductionWorkflowSeeder: no Quarry companies found.');

            return;
        }

        foreach ($companies as $company) {
            if (WorkflowDefinition::withoutGlobalScope('company')->where('company_id', $company->id)->where('internal_key', 'production-record-approval')->exists()) {
                continue;
            }

            WorkflowDefinition::create([
                'company_id' => $company->id,
                'name' => 'Production Record Approval',
                'internal_key' => 'production-record-approval',
                'description' => 'Every submitted production record is reviewed by a Production Manager before it credits stockpiles. Escalates automatically if not actioned within 24 hours.',
                'module' => 'Production Management',
                'entity_type' => ProductionLog::class,
                'status' => 'Published',
                'version' => 1,
                'definition' => [
                    'nodes' => [
                        ['id' => '1', 'type' => 'start', 'position' => ['x' => 60, 'y' => 160], 'config' => []],
                        ['id' => '2', 'type' => 'approval_step', 'position' => ['x' => 320, 'y' => 160], 'config' => [
                            'approver_type' => 'role', 'role' => 'Production Manager',
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

            $this->command?->info("ProductionWorkflowSeeder: Production Record Approval seeded for company {$company->id} ({$company->name}).");
        }
    }
}
