<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Material;
use App\Models\PurchaseRequest;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Real, end-to-end demo data proving the Workflow & Approvals flagship
 * integrations (Leave Requests, Purchase Requests) actually work: wires a
 * real supervisor relationship and User accounts to the existing HR Staff
 * records (neither existed yet for this demo company), then submits real
 * requests through the live WorkflowEngine. Idempotent.
 */
class WorkflowDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::find(15);

        if (! $company || $company->industry !== 'construction') {
            $this->command?->warn('Skipping WorkflowDemoSeeder: demo construction company (id 15) not found.');

            return;
        }

        if (User::withoutGlobalScope('company')->where('email', 'dennis.kiprotich@tanakihub.example')->exists()) {
            $this->command?->info('WorkflowDemoSeeder: demo data already exists, skipping.');

            return;
        }

        DB::transaction(function () {
            $this->wireSupervisorAndApprovers();
            $this->seedLeaveRequest();
            $this->seedPurchaseRequests();
        });

        $this->command?->info('WorkflowDemoSeeder: supervisor chain, approver accounts, and a real Leave/Purchase request submitted through the live engine.');
    }

    protected function wireSupervisorAndApprovers(): void
    {
        $dennis = Staff::withoutGlobalScope('company')->where('company_id', 15)->where('full_name', 'Dennis Kiprotich')->first();
        $agnes = Staff::withoutGlobalScope('company')->where('company_id', 15)->where('full_name', 'Agnes Nduta')->first();
        $patrick = Staff::withoutGlobalScope('company')->where('company_id', 15)->where('full_name', 'Patrick Mwaura')->first();

        if (! $dennis || ! $agnes) {
            return;
        }

        $agnes->update(['supervisor_id' => $dennis->id]);

        $dennisUser = User::create([
            'company_id' => 15,
            'name' => $dennis->full_name,
            'email' => $dennis->email,
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $dennisUser->assignRole('Department Manager');

        if ($patrick) {
            $patrickUser = User::create([
                'company_id' => 15,
                'name' => $patrick->full_name,
                'email' => $patrick->email,
                'password' => 'password',
                'email_verified_at' => now(),
            ]);
            $patrickUser->assignRole('Finance Director');
        }
    }

    protected function seedLeaveRequest(): void
    {
        $agnes = Staff::withoutGlobalScope('company')->where('company_id', 15)->where('full_name', 'Agnes Nduta')->first();

        if (! $agnes) {
            return;
        }

        $leaveType = LeaveType::withoutGlobalScope('company')->where('company_id', 15)->first()
            ?? LeaveType::create(['company_id' => 15, 'name' => 'Annual Leave', 'default_days_per_year' => 21]);

        $leave = LeaveRequest::create([
            'company_id' => 15,
            'staff_id' => $agnes->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => now()->addWeek(),
            'end_date' => now()->addWeek()->addDays(4),
            'status' => 'Pending',
            'reason' => 'Family commitment',
        ]);

        $leave->submitForApproval('leave-request-approval', [
            'title' => 'Leave Request — ' . $agnes->full_name,
            'metadata' => ['staff_id' => $agnes->id],
        ]);
    }

    protected function seedPurchaseRequests(): void
    {
        $material = Material::withoutGlobalScope('company')->where('company_id', 15)->first()
            ?? Material::create(['company_id' => 15, 'name' => 'Portland Cement (50kg)', 'unit' => 'bags', 'unit_cost' => 850, 'reorder_level' => 200, 'quantity_on_hand' => 40]);

        $lowValue = PurchaseRequest::create([
            'company_id' => 15,
            'material_id' => $material->id,
            'quantity' => 10,
            'estimated_cost' => 15000,
            'priority' => 'Medium',
            'source' => 'Manual',
            'status' => 'Pending',
        ]);

        $lowValue->submitForApproval('purchase-request-approval', [
            'title' => 'Purchase Request — ' . $lowValue->pr_number,
            'metadata' => ['amount' => 15000.0],
        ]);

        $highValue = PurchaseRequest::create([
            'company_id' => 15,
            'material_id' => $material->id,
            'quantity' => 500,
            'estimated_cost' => 120000,
            'priority' => 'High',
            'source' => 'Manual',
            'status' => 'Pending',
        ]);

        $highValue->submitForApproval('purchase-request-approval', [
            'title' => 'Purchase Request — ' . $highValue->pr_number,
            'metadata' => ['amount' => 120000.0],
        ]);
    }
}
