<?php

namespace Database\Seeders;

use App\Models\BoqItem;
use App\Models\CashFlowForecast;
use App\Models\ChangeOrder;
use App\Models\Commitment;
use App\Models\Company;
use App\Models\CostCentre;
use App\Models\CostCode;
use App\Models\DailySiteDiary;
use App\Models\DiaryDelay;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PaymentCertificate;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectForecast;
use App\Models\ProgressClaim;
use App\Models\QualityInspection;
use App\Models\RevenueRecognition;
use App\Models\Site;
use App\Models\Staff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Realistic Project Finance demo data for the existing construction demo
 * company/projects (see ConstructionDemoSeeder). Idempotent — bails if cost
 * codes already exist for this company.
 */
class ProjectFinanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::find(15);

        if (! $company || $company->industry !== 'construction') {
            $this->command?->warn('Skipping ProjectFinanceDemoSeeder: demo construction company (id 15) not found.');

            return;
        }

        if (CostCode::withoutGlobalScope('company')->where('company_id', 15)->exists()) {
            $this->command?->info('ProjectFinanceDemoSeeder: data already exists, skipping.');

            return;
        }

        DB::transaction(function () use ($company) {
            $companyId = $company->id;
            $staffId = Staff::withoutGlobalScope('company')->where('company_id', $companyId)->first()?->id;

            $costCodeDefs = [
                ['code' => 'CC-100', 'name' => 'Site Establishment', 'category' => 'Site Establishment'],
                ['code' => 'CC-200', 'name' => 'Earthworks', 'category' => 'Earthworks'],
                ['code' => 'CC-300', 'name' => 'Concrete', 'category' => 'Concrete'],
                ['code' => 'CC-400', 'name' => 'Steel', 'category' => 'Steel'],
                ['code' => 'CC-500', 'name' => 'Masonry', 'category' => 'Masonry'],
                ['code' => 'CC-600', 'name' => 'Roofing', 'category' => 'Roofing'],
                ['code' => 'CC-700', 'name' => 'Electrical', 'category' => 'Electrical'],
                ['code' => 'CC-800', 'name' => 'Mechanical', 'category' => 'Mechanical'],
                ['code' => 'CC-900', 'name' => 'Plumbing', 'category' => 'Plumbing'],
                ['code' => 'CC-1000', 'name' => 'Finishes', 'category' => 'Finishes'],
                ['code' => 'CC-1100', 'name' => 'Equipment', 'category' => 'Equipment'],
                ['code' => 'CC-1200', 'name' => 'Labour', 'category' => 'Labour'],
                ['code' => 'CC-1300', 'name' => 'Subcontractors', 'category' => 'Subcontractors'],
                ['code' => 'CC-1400', 'name' => 'Overheads', 'category' => 'Overheads'],
            ];

            $costCodes = collect($costCodeDefs)->mapWithKeys(function ($def) use ($companyId) {
                $code = CostCode::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'code' => $def['code'],
                    'name' => $def['name'],
                    'category' => $def['category'],
                    'is_active' => true,
                ]);

                return [$def['category'] => $code];
            });

            $projects = Project::withoutGlobalScope('company')->where('company_id', $companyId)->whereNotNull('contract_value')->get();

            $budgetSeq = 0;
            $commitmentSeq = 0;
            $coSeq = 0;
            $claimSeq = 0;
            $certSeq = 0;
            $invoiceSeq = (int) Invoice::withoutGlobalScope('company')->max('id');

            foreach ($projects as $project) {
                $contractValue = (float) $project->contract_value;

                $costCentre = CostCentre::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'code' => 'CTR-' . $project->id,
                    'name' => $project->name . ' — Main Cost Centre',
                    'project_id' => $project->id,
                    'manager_staff_id' => $staffId,
                    'description' => 'Primary cost centre for ' . $project->name,
                ]);

                // Budget lines across the major categories
                $allocations = [
                    'Earthworks' => 0.12, 'Concrete' => 0.22, 'Steel' => 0.15,
                    'Labour' => 0.18, 'Subcontractors' => 0.15, 'Equipment' => 0.08, 'Overheads' => 0.10,
                ];

                foreach ($allocations as $category => $pct) {
                    $budgetSeq++;
                    $approved = round($contractValue * $pct, 2);

                    ProjectBudget::withoutGlobalScope('company')->create([
                        'company_id' => $companyId,
                        'budget_number' => 'BUD-' . str_pad((string) $budgetSeq, 4, '0', STR_PAD_LEFT),
                        'project_id' => $project->id,
                        'cost_centre_id' => $costCentre->id,
                        'cost_code_id' => $costCodes[$category]?->id,
                        'category' => $category,
                        'approved_budget' => $approved,
                        'revised_budget' => 0,
                        'actual_cost' => round($approved * random_int(40, 85) / 100, 2),
                        'last_synced_at' => now(),
                        'status' => 'Approved',
                        'approved_by_id' => $staffId,
                        'approved_at' => now()->subDays(random_int(10, 90)),
                    ]);
                }

                // BOQ items
                $boqDefs = [
                    ['item_code' => 'BOQ-01', 'description' => 'Excavation and earthworks', 'unit' => 'm3', 'quantity' => 5000, 'rate' => 850],
                    ['item_code' => 'BOQ-02', 'description' => 'Reinforced concrete foundations', 'unit' => 'm3', 'quantity' => 1200, 'rate' => 18500],
                    ['item_code' => 'BOQ-03', 'description' => 'Structural steelwork', 'unit' => 'tonnes', 'quantity' => 180, 'rate' => 210000],
                    ['item_code' => 'BOQ-04', 'description' => 'Blockwork walling', 'unit' => 'm2', 'quantity' => 3200, 'rate' => 2400],
                ];

                foreach ($boqDefs as $def) {
                    $completedPct = random_int(20, 90) / 100;

                    BoqItem::withoutGlobalScope('company')->create([
                        'company_id' => $companyId,
                        'project_id' => $project->id,
                        'item_code' => $def['item_code'],
                        'description' => $def['description'],
                        'unit' => $def['unit'],
                        'quantity' => $def['quantity'],
                        'rate' => $def['rate'],
                        'amount' => $def['quantity'] * $def['rate'],
                        'completed_quantity' => round($def['quantity'] * $completedPct, 2),
                        'variation_quantity' => 0,
                    ]);
                }

                // Commitments
                $commitmentSeq++;
                Commitment::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'commitment_number' => 'CMT-' . str_pad((string) $commitmentSeq, 4, '0', STR_PAD_LEFT),
                    'project_id' => $project->id,
                    'type' => 'subcontract',
                    'description' => 'Structural subcontract package',
                    'committed_amount' => round($contractValue * 0.15, 2),
                    'invoiced_to_date' => round($contractValue * 0.08, 2),
                    'status' => 'Active',
                ]);

                // Change orders / variations
                $coSeq++;
                ChangeOrder::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'co_number' => 'CO-' . str_pad((string) $coSeq, 4, '0', STR_PAD_LEFT),
                    'project_id' => $project->id,
                    'type' => 'client_variation',
                    'title' => 'Additional finishes upgrade',
                    'description' => 'Client-requested upgrade to premium finishes.',
                    'reason' => 'Client request',
                    'cost_impact' => round($contractValue * 0.02, 2),
                    'time_impact_days' => 14,
                    'status' => 'Approved',
                    'requested_by_id' => $staffId,
                    'approved_by_id' => $staffId,
                    'approved_at' => now()->subDays(20),
                ]);

                // Progress claim
                $claimSeq++;
                $workCompleted = round($contractValue * random_int(30, 70) / 100, 2);
                $claim = ProgressClaim::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'claim_number' => 'PC-' . str_pad((string) $claimSeq, 4, '0', STR_PAD_LEFT),
                    'project_id' => $project->id,
                    'period_start' => now()->subMonth()->startOfMonth(),
                    'period_end' => now()->subMonth()->endOfMonth(),
                    'previous_certified_amount' => round($workCompleted * 0.4, 2),
                    'work_completed_amount' => $workCompleted,
                    'amount_claimed' => $workCompleted,
                    'retention_percent' => 10,
                    'vat_percent' => 16,
                    'status' => 'Certified',
                    'submitted_by_id' => $staffId,
                    'certified_by_id' => $staffId,
                    'certified_at' => now()->subDays(15),
                ]);
                $claim->certify();

                // Client invoice linked to the claim
                $invoiceSeq++;
                $invoiceNumber = 'CINV-' . str_pad((string) $invoiceSeq, 4, '0', STR_PAD_LEFT);

                Invoice::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'invoice_number' => $invoiceNumber,
                    'project_id' => $project->id,
                    'progress_claim_id' => $claim->id,
                    'client_id' => $project->client_id,
                    'type' => 'invoice',
                    'invoice_date' => now()->subDays(15),
                    'due_date' => now()->addDays(15),
                    'line_items' => [],
                    'subtotal' => $claim->certified_amount,
                    'tax' => 0,
                    'total_amount' => $claim->certified_amount,
                    'status' => 'sent',
                ]);

                if ($project->client_id) {
                    \App\Models\Payment::withoutGlobalScope('company')->create([
                        'company_id' => $companyId,
                        'project_id' => $project->id,
                        'client_id' => $project->client_id,
                        'amount' => round($claim->certified_amount * 0.6, 2),
                        'payment_method' => 'Bank Transfer',
                        'status' => 'Paid',
                        'invoice_number' => $invoiceNumber . '-P1',
                        'payment_date' => now()->subDays(10),
                    ]);
                }

                // Client-side payment certificate for the claim
                $certSeq++;
                $cert = PaymentCertificate::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'certificate_number' => 'PCT-' . str_pad((string) $certSeq, 4, '0', STR_PAD_LEFT),
                    'project_id' => $project->id,
                    'progress_claim_id' => $claim->id,
                    'certificate_type' => 'Interim',
                    'scope' => 'client',
                    'period_covered' => now()->subMonth()->format('F Y'),
                    'gross_amount' => $workCompleted,
                    'advance_recovery_amount' => 0,
                    'previous_certified_amount' => $claim->previous_certified_amount,
                    'status' => 'Approved',
                    'approved_by_id' => $staffId,
                    'approved_at' => now()->subDays(14),
                ]);
                $cert->approve();

                // Revenue recognition snapshot
                RevenueRecognition::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'project_id' => $project->id,
                    'period_month' => now()->startOfMonth(),
                    'method' => 'percentage_of_completion',
                    'percent_complete' => (float) ($project->progress_percent ?? 50),
                    'contract_revenue' => $contractValue,
                    'earned_revenue' => $workCompleted,
                    'recognized_revenue' => round($workCompleted * 0.8, 2),
                    'deferred_revenue' => round($workCompleted * 0.2, 2),
                ]);

                // Forecast snapshot
                ProjectForecast::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'project_id' => $project->id,
                    'forecast_date' => now(),
                    'forecast_final_cost' => round($contractValue * 0.92, 2),
                    'forecast_revenue' => $contractValue,
                    'forecast_profit' => round($contractValue * 0.08, 2),
                    'estimated_completion_date' => $project->end_date,
                    'cost_performance_index' => 1.05,
                    'schedule_performance_index' => 0.97,
                    'forecasted_by_id' => $staffId,
                ]);

                // Site-level operating expenses (Cost Control: Site Expenses / Utilities / Transport / Overheads)
                $expenseDefs = [
                    ['category' => 'Site Expenses', 'amount' => round($contractValue * 0.008, 2), 'description' => 'Site office and welfare facilities'],
                    ['category' => 'Utilities', 'amount' => round($contractValue * 0.003, 2), 'description' => 'Water and electricity for site'],
                    ['category' => 'Transport', 'amount' => round($contractValue * 0.004, 2), 'description' => 'Materials haulage and site transport'],
                    ['category' => 'Overheads', 'amount' => round($contractValue * 0.005, 2), 'description' => 'Project overheads and admin costs'],
                ];

                foreach ($expenseDefs as $def) {
                    Expense::withoutGlobalScope('company')->create([
                        'company_id' => $companyId,
                        'project_id' => $project->id,
                        'category' => $def['category'],
                        'amount' => $def['amount'],
                        'date' => now()->subDays(random_int(5, 45)),
                        'payment_method' => 'Bank Transfer',
                        'description' => $def['description'],
                    ]);
                }

                // Daily Site Diary integration: a delay with an estimated cost impact
                $projectSiteId = Site::withoutGlobalScope('company')->where('project_id', $project->id)->value('id');

                if ($projectSiteId) {
                    $diary = DailySiteDiary::withoutGlobalScope('company')->create([
                        'company_id' => $companyId,
                        'site_id' => $projectSiteId,
                        'project_id' => $project->id,
                        'date' => now()->subDays(10),
                        'weather' => 'Heavy Rain',
                        'workers_on_site' => random_int(20, 60),
                        'work_summary' => 'Works paused for part of the day due to weather.',
                        'logged_by_id' => $staffId,
                    ]);

                    DiaryDelay::withoutGlobalScope('company')->create([
                        'company_id' => $companyId,
                        'daily_site_diary_id' => $diary->id,
                        'type' => 'Weather',
                        'reason' => 'Heavy rainfall halted concrete pours and earthworks for most of the shift.',
                        'duration_hours' => 6,
                        'impact' => 'Medium',
                        'cost_impact' => round($contractValue * 0.0015, 2),
                        'status' => 'Resolved',
                    ]);

                    // Quality Control integration: a failed inspection with rework cost
                    QualityInspection::withoutGlobalScope('company')->create([
                        'company_id' => $companyId,
                        'site_id' => $projectSiteId,
                        'inspector_id' => null,
                        'date' => now()->subDays(8),
                        'checklist_item' => 'Concrete cover and reinforcement placement',
                        'result' => 'Fail',
                        'notes' => 'Insufficient concrete cover on beam reinforcement — section demolished and recast.',
                        'rework_cost' => round($contractValue * 0.001, 2),
                    ]);
                }

                // Cash flow forecast entries (next 3 months)
                for ($i = 1; $i <= 3; $i++) {
                    CashFlowForecast::withoutGlobalScope('company')->create([
                        'company_id' => $companyId,
                        'project_id' => $project->id,
                        'period_month' => now()->addMonths($i)->startOfMonth(),
                        'category' => 'Progress Billing',
                        'direction' => 'in',
                        'amount' => round($contractValue * 0.06, 2),
                        'entry_type' => 'forecast',
                        'note' => 'Projected progress claim',
                    ]);
                }
            }
        });

        $this->command?->info('ProjectFinanceDemoSeeder: seeded budgets, BOQ, commitments, change orders, progress claims, invoices, certificates, revenue recognition and forecasts.');
    }
}
