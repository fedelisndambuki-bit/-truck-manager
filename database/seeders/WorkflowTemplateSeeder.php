<?php

namespace Database\Seeders;

use App\Models\WorkflowDefinition;
use Illuminate\Database\Seeder;

/**
 * Platform-wide starter templates for the Workflow Designer's "Approval
 * Templates" gallery — company_id is deliberately null (visible to every
 * tenant, cloned into a tenant-owned Draft on "Use Template"). Idempotent.
 */
class WorkflowTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (WorkflowDefinition::withoutGlobalScope('company')->whereNull('company_id')->exists()) {
            $this->command?->info('WorkflowTemplateSeeder: templates already exist, skipping.');

            return;
        }

        WorkflowDefinition::create([
            'company_id' => null,
            'name' => 'Simple Sequential Approval',
            'internal_key' => 'template-simple-sequential',
            'description' => 'A single approver signs off before the request completes. Good starting point for most approvals.',
            'module' => 'Core',
            'status' => 'Published',
            'version' => 1,
            'definition' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'start', 'position' => ['x' => 60, 'y' => 180], 'config' => []],
                    ['id' => '2', 'type' => 'approval_step', 'position' => ['x' => 320, 'y' => 180], 'config' => [
                        'approver_type' => 'role', 'role' => 'Department Manager',
                        'approver_resolution_mode' => 'any_one', 'escalation_hours' => 48,
                    ]],
                    ['id' => '3', 'type' => 'end', 'position' => ['x' => 580, 'y' => 180], 'config' => []],
                ],
                'edges' => [
                    ['from' => '1', 'to' => '2'],
                    ['from' => '2', 'to' => '3'],
                ],
            ],
            'node_types_used' => ['start', 'approval_step', 'end'],
        ]);

        WorkflowDefinition::create([
            'company_id' => null,
            'name' => 'Two-Level Amount-Based Approval',
            'internal_key' => 'template-two-level-amount',
            'description' => 'Low-value requests go to a Department Manager. High-value requests (amount over threshold) also require Finance Director sign-off.',
            'module' => 'Core',
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

        WorkflowDefinition::create([
            'company_id' => null,
            'name' => 'Parallel Committee Approval',
            'internal_key' => 'template-parallel-committee',
            'description' => 'Two approvers review at the same time; the request only proceeds once both have responded.',
            'module' => 'Core',
            'status' => 'Published',
            'version' => 1,
            'definition' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'start', 'position' => ['x' => 40, 'y' => 200], 'config' => []],
                    ['id' => '2', 'type' => 'parallel', 'position' => ['x' => 260, 'y' => 200], 'config' => []],
                    ['id' => '3', 'type' => 'approval_step', 'position' => ['x' => 500, 'y' => 80], 'config' => [
                        'approver_type' => 'committee', 'committee_role' => 'Operations Director',
                        'approver_resolution_mode' => 'any_one', 'escalation_hours' => 48,
                    ]],
                    ['id' => '4', 'type' => 'approval_step', 'position' => ['x' => 500, 'y' => 320], 'config' => [
                        'approver_type' => 'committee', 'committee_role' => 'Finance Director',
                        'approver_resolution_mode' => 'any_one', 'escalation_hours' => 48,
                    ]],
                    ['id' => '5', 'type' => 'end', 'position' => ['x' => 760, 'y' => 200], 'config' => []],
                ],
                'edges' => [
                    ['from' => '1', 'to' => '2'],
                    ['from' => '2', 'to' => '3'],
                    ['from' => '2', 'to' => '4'],
                    ['from' => '3', 'to' => '5'],
                    ['from' => '4', 'to' => '5'],
                ],
            ],
            'node_types_used' => ['start', 'parallel', 'approval_step', 'end'],
        ]);

        $this->command?->info('WorkflowTemplateSeeder: 3 platform templates seeded.');
    }
}
