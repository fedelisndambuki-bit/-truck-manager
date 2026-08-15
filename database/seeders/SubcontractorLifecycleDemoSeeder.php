<?php

namespace Database\Seeders;

use App\Models\BidSubmission;
use App\Models\Company;
use App\Models\Contract;
use App\Models\PaymentCertificate;
use App\Models\Project;
use App\Models\Site;
use App\Models\Staff;
use App\Models\Subcontractor;
use App\Models\SubcontractorAssignment;
use App\Models\SubcontractorPerformanceReview;
use App\Models\SubcontractorPrequalification;
use App\Models\TenderInvitation;
use App\Models\Variation;
use Illuminate\Database\Seeder;

/**
 * Extends the Construction demo company (Company #15) with the Subcontractor
 * lifecycle ConstructionDemoSeeder's bare Subcontractor rows never exercised:
 * prequalification -> tender invitation -> bid -> award -> real Contract ->
 * site assignment -> performance review -> variation, plus a real
 * subcontractor-scoped PaymentCertificate — closing the gap where
 * ProjectFinanceService::subcontractorCost() always read $0 because no
 * Contract existed for it to join through. Idempotent.
 */
class SubcontractorLifecycleDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::find(15);

        if (! $company || $company->industry !== 'construction') {
            $this->command?->warn('Skipping SubcontractorLifecycleDemoSeeder: Company #15 not found or not a construction company.');

            return;
        }

        $companyId = $company->id;

        if (Contract::withoutGlobalScope('company')->where('company_id', $companyId)->exists()) {
            $this->command?->info('SubcontractorLifecycleDemoSeeder: demo data already present, skipping.');

            return;
        }

        $projects = Project::withoutGlobalScope('company')->where('company_id', $companyId)->get();
        $subcontractors = Subcontractor::withoutGlobalScope('company')->where('company_id', $companyId)->get();
        $staff = Staff::withoutGlobalScope('company')->where('company_id', $companyId)->get();

        if ($projects->isEmpty() || $subcontractors->count() < 2) {
            $this->command?->warn('Skipping SubcontractorLifecycleDemoSeeder: not enough Projects/Subcontractors found (run ConstructionDemoSeeder first).');

            return;
        }

        $project = $projects->first();
        $site = Site::withoutGlobalScope('company')->where('project_id', $project->id)->first();
        $siteManager = $staff->get(1) ?? $staff->first();
        $supervisor = $staff->get(6) ?? $staff->first();
        $reviewer = $staff->first();

        // ---- Prequalification for every subcontractor ----
        foreach ($subcontractors as $sub) {
            SubcontractorPrequalification::create([
                'company_id' => $companyId,
                'subcontractor_id' => $sub->id,
                'financial_capacity_notes' => 'Bank reference and audited accounts reviewed, capacity adequate for tendered scope.',
                'technical_capacity_notes' => 'Relevant trade experience confirmed via past project references.',
                'insurance_verified' => true,
                'safety_record_notes' => 'No major incidents on file.',
                'score' => random_int(70, 92),
                'approval_status' => 'Approved',
                'approved_by_id' => $reviewer?->id,
                'approved_at' => now()->subMonths(random_int(2, 6)),
            ]);
        }

        // ---- Tender: invite the electrical + structural steel subs, award to one ----
        $electrical = $subcontractors->firstWhere('trade_specialty', 'Electrical') ?? $subcontractors[0];
        $structural = $subcontractors->firstWhere('trade_specialty', 'Structural Steel') ?? $subcontractors[1];

        $tender = TenderInvitation::create([
            'company_id' => $companyId,
            'project_id' => $project->id,
            'site_id' => $site?->id,
            'scope_description' => 'Structural steelworks package — ' . $project->name,
            'closing_date' => now()->subDays(20),
            'estimated_value' => 5000000,
            'status' => 'Open',
        ]);

        $tender->invitedSubcontractors()->attach([
            $structural->id => ['invited_at' => now()->subDays(35), 'responded' => true],
            $electrical->id => ['invited_at' => now()->subDays(35), 'responded' => true],
        ]);

        $winningBid = BidSubmission::create([
            'company_id' => $companyId,
            'tender_invitation_id' => $tender->id,
            'subcontractor_id' => $structural->id,
            'bid_amount' => 4750000,
            'submission_date' => now()->subDays(28),
            'technical_score' => 88,
            'commercial_score' => 82,
            'status' => 'Submitted',
        ]);

        BidSubmission::create([
            'company_id' => $companyId,
            'tender_invitation_id' => $tender->id,
            'subcontractor_id' => $electrical->id,
            'bid_amount' => 5150000,
            'submission_date' => now()->subDays(27),
            'technical_score' => 75,
            'commercial_score' => 70,
            'status' => 'Submitted',
        ]);

        $winningBid->award(); // rejects the sibling bid, marks this one Awarded, closes the tender

        // ---- Real Contract for the awarded bid ----
        $contract = Contract::create([
            'company_id' => $companyId,
            'subcontractor_id' => $structural->id,
            'project_id' => $project->id,
            'site_id' => $site?->id,
            'tender_invitation_id' => $tender->id,
            'scope_summary' => 'Structural steelworks: fabrication, delivery and erection per approved drawings.',
            'contract_value' => $winningBid->bid_amount,
            'retention_percent' => 10,
            'start_date' => now()->subDays(25),
            'completion_date' => now()->addMonths(4),
            'payment_terms' => 'Monthly certified progress payments, 10% retention',
            'performance_bond_amount' => round((float) $winningBid->bid_amount * 0.1, 2),
            'insurance_required' => true,
            'status' => 'Active',
            'signed_date' => now()->subDays(24),
        ]);

        // ---- Site assignment ----
        SubcontractorAssignment::create([
            'company_id' => $companyId,
            'subcontractor_id' => $structural->id,
            'contract_id' => $contract->id,
            'project_id' => $project->id,
            'site_id' => $site?->id,
            'construction_phase' => 'Structural Works',
            'work_area' => 'Main structural frame',
            'site_manager_id' => $siteManager?->id,
            'supervisor_id' => $supervisor?->id,
            'status' => 'Active',
        ]);

        // ---- Performance review ----
        SubcontractorPerformanceReview::create([
            'company_id' => $companyId,
            'subcontractor_id' => $structural->id,
            'contract_id' => $contract->id,
            'project_id' => $project->id,
            'review_date' => now()->subDays(5),
            'quality_of_work_rating' => 4,
            'timeliness_rating' => 4,
            'safety_rating' => 5,
            'communication_rating' => 4,
            'cost_control_rating' => 3,
            'issue_resolution_rating' => 4,
            'reviewed_by_id' => $reviewer?->id,
            'comments' => 'Solid progress on the structural package; one minor delay resolved without cost impact.',
        ]);

        // ---- Variation (extra work, approved) ----
        $variation = Variation::create([
            'company_id' => $companyId,
            'contract_id' => $contract->id,
            'reason' => 'Additional bracing required per revised structural engineer instruction.',
            'cost_impact' => round((float) $contract->contract_value * 0.03, 2),
            'time_extension_days' => 5,
            'status' => 'Requested',
            'requested_by_id' => $siteManager?->id,
        ]);
        $variation->approve();

        // ---- Subcontractor-scoped payment certificate (closes the subcontractorCost() gap) ----
        $certified = round((float) $contract->contract_value * 0.35, 2);
        $certificate = PaymentCertificate::create([
            'company_id' => $companyId,
            'contract_id' => $contract->id,
            'project_id' => $project->id,
            'scope' => 'subcontractor',
            'gross_amount' => $certified,
            'advance_recovery_amount' => 0,
            'previous_certified_amount' => 0,
            'status' => 'Draft',
            'approved_by_id' => $reviewer?->id,
        ]);
        $certificate->approve(); // computes retention/net_amount_due and creates the RetentionEntry

        $this->command?->info('SubcontractorLifecycleDemoSeeder: subcontractor lifecycle seeded for Company #' . $companyId);
    }
}
