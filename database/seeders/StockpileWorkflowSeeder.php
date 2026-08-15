<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ProductionStockTransfer;
use App\Models\StockpileAdjustment;
use App\Models\StockpilePhysicalCount;
use App\Models\WorkflowDefinition;
use Illuminate\Database\Seeder;

/**
 * Seeds the 3 single-approver WorkflowDefinitions for Stockpile Management
 * (Stock Transfer, Stock Adjustment, Physical Count Approval) for every real
 * Quarry company — mirrors ProductionWorkflowSeeder/CrushingPlantWorkflowSeeder's
 * exact pattern, role 'Inventory Controller' throughout. Idempotent.
 */
class StockpileWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->where('industry', 'quarry')->get();

        if ($companies->isEmpty()) {
            $this->command?->warn('Skipping StockpileWorkflowSeeder: no Quarry companies found.');

            return;
        }

        $definitions = [
            [
                'key' => 'stock-transfer-approval',
                'name' => 'Stock Transfer Approval',
                'entity' => ProductionStockTransfer::class,
                'description' => 'Every submitted stock transfer is reviewed by an Inventory Controller before it moves material between stockpiles. Escalates automatically if not actioned within 24 hours.',
            ],
            [
                'key' => 'stock-adjustment-approval',
                'name' => 'Stock Adjustment Approval',
                'entity' => StockpileAdjustment::class,
                'description' => 'Every submitted stock adjustment is reviewed by an Inventory Controller before it changes a stockpile\'s recorded quantity. Escalates automatically if not actioned within 24 hours.',
            ],
            [
                'key' => 'physical-count-approval',
                'name' => 'Physical Count Approval',
                'entity' => StockpilePhysicalCount::class,
                'description' => 'Every submitted physical stock count is reviewed by an Inventory Controller before the system quantity is reconciled to the physical count. Escalates automatically if not actioned within 24 hours.',
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
                    'module' => 'Stockpile Management',
                    'entity_type' => $definition['entity'],
                    'status' => 'Published',
                    'version' => 1,
                    'definition' => [
                        'nodes' => [
                            ['id' => '1', 'type' => 'start', 'position' => ['x' => 60, 'y' => 160], 'config' => []],
                            ['id' => '2', 'type' => 'approval_step', 'position' => ['x' => 320, 'y' => 160], 'config' => [
                                'approver_type' => 'role', 'role' => 'Inventory Controller',
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

                $this->command?->info("StockpileWorkflowSeeder: {$definition['name']} seeded for company {$company->id} ({$company->name}).");
            }
        }
    }
}
