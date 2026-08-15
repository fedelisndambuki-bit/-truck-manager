<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\DispatchOrder;
use App\Models\WeighbridgeException;
use App\Models\WorkflowDefinition;
use Illuminate\Database\Seeder;

/**
 * Seeds the 2 single-approver WorkflowDefinitions for Smart Weighbridge
 * (Dispatch Weight Approval, Weighbridge Exception Approval) for every real
 * Quarry company — mirrors StockpileWorkflowSeeder's exact pattern, role
 * 'Weighbridge Supervisor' throughout. Idempotent.
 */
class SmartWeighbridgeWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->where('industry', 'quarry')->get();

        if ($companies->isEmpty()) {
            $this->command?->warn('Skipping SmartWeighbridgeWorkflowSeeder: no Quarry companies found.');

            return;
        }

        $definitions = [
            [
                'key' => 'dispatch-weight-approval',
                'name' => 'Dispatch Weight Approval',
                'entity' => DispatchOrder::class,
                'escalation_hours' => 12,
                'description' => 'Every dispatch with an overweight or critical weight variance is reviewed by a Weighbridge Supervisor before release. Escalates automatically if not actioned within 12 hours (a truck is physically waiting at the weighbridge).',
            ],
            [
                'key' => 'weighbridge-exception-approval',
                'name' => 'Weighbridge Exception Approval',
                'entity' => WeighbridgeException::class,
                'escalation_hours' => 24,
                'description' => 'Every submitted weighbridge exception (overload, wrong product/stockpile, scale failure, manual override, etc.) is reviewed by a Weighbridge Supervisor. Escalates automatically if not actioned within 24 hours.',
            ],
        ];

        foreach ($companies as $company) {
            foreach ($definitions as $definition) {
                if (WorkflowDefinition::withoutGlobalScope('company')->where('company_id', $company->id)->where('internal_key', $definition['key'])->exists()) {
                    continue;
                }

                WorkflowDefinition::create([
                    'company_id' => $company->id,
                    'name' => $definition['name'],
                    'internal_key' => $definition['key'],
                    'description' => $definition['description'],
                    'module' => 'Smart Weighbridge',
                    'entity_type' => $definition['entity'],
                    'status' => 'Published',
                    'version' => 1,
                    'definition' => [
                        'nodes' => [
                            ['id' => '1', 'type' => 'start', 'position' => ['x' => 60, 'y' => 160], 'config' => []],
                            ['id' => '2', 'type' => 'approval_step', 'position' => ['x' => 320, 'y' => 160], 'config' => [
                                'approver_type' => 'role', 'role' => 'Weighbridge Supervisor',
                                'approver_resolution_mode' => 'any_one', 'escalation_hours' => $definition['escalation_hours'],
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

                $this->command?->info("SmartWeighbridgeWorkflowSeeder: {$definition['name']} seeded for company {$company->id} ({$company->name}).");
            }
        }
    }
}
