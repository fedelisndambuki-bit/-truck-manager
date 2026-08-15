<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds the two real, end-to-end flagship WorkflowDefinitions — Leave
 * Request Approval (HR) and Purchase Request Approval (Procurement) — for
 * the demo construction company (see ConstructionDemoSeeder). Idempotent.
 */
class WorkflowFlagshipSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::find(15);

        if (! $company || $company->industry !== 'construction') {
            $this->command?->warn('Skipping WorkflowFlagshipSeeder: demo construction company (id 15) not found.');

            return;
        }

        if (WorkflowDefinition::withoutGlobalScope('company')->where('company_id', 15)->where('internal_key', 'leave-request-approval')->exists()) {
            $this->command?->info('WorkflowFlagshipSeeder: flagship workflows already exist, skipping.');

            return;
        }

        WorkflowDefinition::create([
            'company_id' => 15,
            'name' => 'Leave Request Approval',
            'internal_key' => 'leave-request-approval',
            'description' => 'Routes every leave request to the requesting employee\'s direct supervisor. Escalates to the supervisor\'s own supervisor after 48 hours.',
            'module' => 'HR',
            'entity_type' => \App\Models\LeaveRequest::class,
            'status' => 'Published',
            'version' => 1,
            'definition' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'start', 'position' => ['x' => 60, 'y' => 160], 'config' => []],
                    ['id' => '2', 'type' => 'approval_step', 'position' => ['x' => 320, 'y' => 160], 'config' => [
                        'approver_type' => 'supervisor_chain', 'level' => 1,
                        'approver_resolution_mode' => 'any_one', 'escalation_hours' => 48,
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

        WorkflowSetting::setSetting(15, 'purchase_request_amount_threshold', 50000, 'integer');

        WorkflowDefinition::create([
            'company_id' => 15,
            'name' => 'Purchase Request Approval',
            'internal_key' => 'purchase-request-approval',
            'description' => 'Low-value requests need only a Department Manager. Requests over the configured amount threshold also require Finance Director sign-off.',
            'module' => 'Finance',
            'entity_type' => \App\Models\PurchaseRequest::class,
            'status' => 'Published',
            'version' => 1,
            'definition' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'start', 'position' => ['x' => 40, 'y' => 220], 'config' => []],
                    ['id' => '2', 'type' => 'condition', 'position' => ['x' => 260, 'y' => 220], 'config' => []],
                    ['id' => '3', 'type' => 'approval_step', 'position' => ['x' => 500, 'y' => 100], 'config' => [
                        'approver_type' => 'role', 'role' => 'Department Manager',
                        'approver_resolution_mode' => 'any_one', 'escalation_hours' => 48,
                    ]],
                    ['id' => '4', 'type' => 'approval_step', 'position' => ['x' => 500, 'y' => 340], 'config' => [
                        'approver_type' => 'role', 'role' => 'Department Manager',
                        'approver_resolution_mode' => 'any_one', 'escalation_hours' => 24,
                    ]],
                    ['id' => '5', 'type' => 'approval_step', 'position' => ['x' => 760, 'y' => 340], 'config' => [
                        'approver_type' => 'role', 'role' => 'Finance Director',
                        'approver_resolution_mode' => 'any_one', 'escalation_hours' => 24,
                    ]],
                    ['id' => '6', 'type' => 'end', 'position' => ['x' => 1000, 'y' => 220], 'config' => []],
                ],
                'edges' => [
                    ['from' => '1', 'to' => '2'],
                    ['from' => '2', 'to' => '3', 'condition' => ['field' => 'amount', 'operator' => '<=', 'value' => 50000]],
                    ['from' => '2', 'to' => '4', 'condition' => ['field' => 'amount', 'operator' => '>', 'value' => 50000]],
                    ['from' => '3', 'to' => '6'],
                    ['from' => '4', 'to' => '5'],
                    ['from' => '5', 'to' => '6'],
                ],
            ],
            'node_types_used' => ['start', 'condition', 'approval_step', 'end'],
        ]);

        $this->command?->info('WorkflowFlagshipSeeder: Leave Request Approval + Purchase Request Approval seeded for company 15.');
    }
}
