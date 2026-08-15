<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\DispatchOrder;
use App\Models\LabSample;
use App\Models\WorkflowDefinition;
use Illuminate\Database\Seeder;

/**
 * Seeds the 2 WorkflowDefinitions for Laboratory & Quality Control for every
 * real Quarry company — mirrors SmartWeighbridgeWorkflowSeeder's exact
 * pattern. 'dispatch-quality-hold-approval' is the single-approver gate the
 * new DispatchOrder::runDecisionEngine() quality check submits to (approver
 * role 'Quality Manager' — a role that already exists with staff assigned
 * in prior demo seeders, safer to bootstrap than the brand-new 'Laboratory
 * Manager' role which may have zero assigned users at first deploy).
 * 'lab-sample-quality-approval' is the real multi-stage chain backing the
 * "Quality Approvals" sidebar item (Laboratory Review -> Quality Manager
 * Approval -> Production Approval -> Stock Release). Idempotent.
 */
class LaboratoryQualityControlWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->where('industry', 'quarry')->get();

        if ($companies->isEmpty()) {
            $this->command?->warn('Skipping LaboratoryQualityControlWorkflowSeeder: no Quarry companies found.');

            return;
        }

        foreach ($companies as $company) {
            $this->seedDispatchQualityHoldApproval($company);
            $this->seedLabSampleQualityApproval($company);
        }
    }

    protected function seedDispatchQualityHoldApproval(Company $company): void
    {
        $key = 'dispatch-quality-hold-approval';

        if (WorkflowDefinition::withoutGlobalScope('company')->where('company_id', $company->id)->where('internal_key', $key)->exists()) {
            return;
        }

        WorkflowDefinition::create([
            'company_id' => $company->id,
            'name' => 'Dispatch Quality Hold Approval',
            'internal_key' => $key,
            'description' => 'Any dispatch order whose stockpile carries an Active QualityHold is blocked from auto-approval and escalated to a Quality Manager for review. Escalates automatically if not actioned within 12 hours (a truck may be physically waiting at the weighbridge).',
            'module' => 'Laboratory & Quality Control',
            'entity_type' => DispatchOrder::class,
            'status' => 'Published',
            'version' => 1,
            'definition' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'start', 'position' => ['x' => 60, 'y' => 160], 'config' => []],
                    ['id' => '2', 'type' => 'approval_step', 'position' => ['x' => 320, 'y' => 160], 'config' => [
                        'approver_type' => 'role', 'role' => 'Quality Manager',
                        'approver_resolution_mode' => 'any_one', 'escalation_hours' => 12,
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

        $this->command?->info("LaboratoryQualityControlWorkflowSeeder: Dispatch Quality Hold Approval seeded for company {$company->id} ({$company->name}).");
    }

    protected function seedLabSampleQualityApproval(Company $company): void
    {
        $key = 'lab-sample-quality-approval';

        if (WorkflowDefinition::withoutGlobalScope('company')->where('company_id', $company->id)->where('internal_key', $key)->exists()) {
            return;
        }

        WorkflowDefinition::create([
            'company_id' => $company->id,
            'name' => 'Lab Sample Quality Approval',
            'internal_key' => $key,
            'description' => 'Every lab sample that completes testing is reviewed through a 4-stage chain — Laboratory Review, Quality Manager Approval, Production Approval, Stock Release — before the tested material is approved for stock availability and dispatch.',
            'module' => 'Laboratory & Quality Control',
            'entity_type' => LabSample::class,
            'status' => 'Published',
            'version' => 1,
            'definition' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'start', 'position' => ['x' => 40, 'y' => 160], 'config' => []],
                    ['id' => '2', 'type' => 'approval_step', 'position' => ['x' => 220, 'y' => 160], 'config' => [
                        'approver_type' => 'role', 'role' => 'Laboratory Manager',
                        'approver_resolution_mode' => 'any_one', 'escalation_hours' => 24,
                    ]],
                    ['id' => '3', 'type' => 'approval_step', 'position' => ['x' => 400, 'y' => 160], 'config' => [
                        'approver_type' => 'role', 'role' => 'Quality Manager',
                        'approver_resolution_mode' => 'any_one', 'escalation_hours' => 24,
                    ]],
                    ['id' => '4', 'type' => 'approval_step', 'position' => ['x' => 580, 'y' => 160], 'config' => [
                        'approver_type' => 'role', 'role' => 'Production Manager',
                        'approver_resolution_mode' => 'any_one', 'escalation_hours' => 24,
                    ]],
                    ['id' => '5', 'type' => 'approval_step', 'position' => ['x' => 760, 'y' => 160], 'config' => [
                        'approver_type' => 'role', 'role' => 'Inventory Controller',
                        'approver_resolution_mode' => 'any_one', 'escalation_hours' => 24,
                    ]],
                    ['id' => '6', 'type' => 'end', 'position' => ['x' => 940, 'y' => 160], 'config' => []],
                ],
                'edges' => [
                    ['from' => '1', 'to' => '2'],
                    ['from' => '2', 'to' => '3'],
                    ['from' => '3', 'to' => '4'],
                    ['from' => '4', 'to' => '5'],
                    ['from' => '5', 'to' => '6'],
                ],
            ],
            'node_types_used' => ['start', 'approval_step', 'end'],
        ]);

        $this->command?->info("LaboratoryQualityControlWorkflowSeeder: Lab Sample Quality Approval seeded for company {$company->id} ({$company->name}).");
    }
}
