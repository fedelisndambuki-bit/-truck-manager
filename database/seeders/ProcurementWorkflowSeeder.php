<?php

namespace Database\Seeders;

use App\Models\WorkflowDefinition;
use Illuminate\Database\Seeder;

/**
 * Platform-wide workflow definitions for every Procurement approval need —
 * company_id deliberately null (visible/usable by every tenant, same as
 * WorkflowTemplateSeeder's starter gallery). Also the very first time
 * 'purchase-request-approval' is seeded anywhere — PurchaseRequestResource's
 * existing "Submit for Approval" action has called this key since before
 * this module existed, but no definition previously existed for it. Idempotent.
 */
class ProcurementWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedIfMissing(
            'purchase-request-approval',
            'Purchase Requisition Approval',
            'Department Manager approves; requisitions over 100,000 also require Finance Director sign-off.',
            100000
        );

        $this->seedIfMissing(
            'purchase-order-approval',
            'Purchase Order Approval',
            'Department Manager approves; orders over 250,000 also require Finance Director sign-off.',
            250000
        );

        $this->seedIfMissing(
            'blanket-po-approval',
            'Blanket Purchase Order Approval',
            'Procurement Director and Finance Director both sign off before a blanket order is activated.',
            null,
            parallel: true
        );

        $this->seedIfMissing(
            'supplier-contract-approval',
            'Supplier Contract Approval',
            'Procurement Director approves; contracts over 500,000 also require Finance Director sign-off.',
            500000
        );

        $this->seedIfMissing(
            'budget-override-approval',
            'Budget Override Approval',
            'A single Finance Director sign-off to proceed with a purchase that exceeds its budget allocation.',
            null,
            simple: true
        );

        $this->command?->info('ProcurementWorkflowSeeder: platform approval templates seeded.');
    }

    protected function seedIfMissing(string $key, string $name, string $description, ?int $threshold, bool $parallel = false, bool $simple = false): void
    {
        if (WorkflowDefinition::withoutGlobalScope('company')->whereNull('company_id')->where('internal_key', $key)->exists()) {
            return;
        }

        WorkflowDefinition::create([
            'company_id' => null,
            'name' => $name,
            'internal_key' => $key,
            'description' => $description,
            'module' => 'Procurement',
            'status' => 'Published',
            'version' => 1,
            'definition' => match (true) {
                $simple => $this->simpleSequentialDefinition(),
                $parallel => $this->parallelCommitteeDefinition(),
                default => $this->twoLevelAmountDefinition($threshold),
            },
            'node_types_used' => $simple ? ['start', 'approval_step', 'end'] : ($parallel ? ['start', 'parallel', 'approval_step', 'end'] : ['start', 'condition', 'approval_step', 'end']),
        ]);
    }

    protected function simpleSequentialDefinition(): array
    {
        return [
            'nodes' => [
                ['id' => '1', 'type' => 'start', 'position' => ['x' => 60, 'y' => 180], 'config' => []],
                ['id' => '2', 'type' => 'approval_step', 'position' => ['x' => 320, 'y' => 180], 'config' => [
                    'approver_type' => 'role', 'role' => 'Finance Director',
                    'approver_resolution_mode' => 'any_one', 'escalation_hours' => 24,
                ]],
                ['id' => '3', 'type' => 'end', 'position' => ['x' => 580, 'y' => 180], 'config' => []],
            ],
            'edges' => [
                ['from' => '1', 'to' => '2'],
                ['from' => '2', 'to' => '3'],
            ],
        ];
    }

    protected function twoLevelAmountDefinition(int $threshold): array
    {
        return [
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
                ['from' => '2', 'to' => '3', 'condition' => ['field' => 'amount', 'operator' => '<=', 'value' => $threshold]],
                ['from' => '2', 'to' => '4', 'condition' => ['field' => 'amount', 'operator' => '>', 'value' => $threshold]],
                ['from' => '3', 'to' => '6'],
                ['from' => '4', 'to' => '5'],
                ['from' => '5', 'to' => '6'],
            ],
        ];
    }

    protected function parallelCommitteeDefinition(): array
    {
        return [
            'nodes' => [
                ['id' => '1', 'type' => 'start', 'position' => ['x' => 40, 'y' => 200], 'config' => []],
                ['id' => '2', 'type' => 'parallel', 'position' => ['x' => 260, 'y' => 200], 'config' => []],
                ['id' => '3', 'type' => 'approval_step', 'position' => ['x' => 500, 'y' => 80], 'config' => [
                    'approver_type' => 'role', 'role' => 'Procurement Director',
                    'approver_resolution_mode' => 'any_one', 'escalation_hours' => 48,
                ]],
                ['id' => '4', 'type' => 'approval_step', 'position' => ['x' => 500, 'y' => 320], 'config' => [
                    'approver_type' => 'role', 'role' => 'Finance Director',
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
        ];
    }
}
