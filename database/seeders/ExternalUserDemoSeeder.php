<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ExternalOrganization;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Real demo data proving the External User & Project-Scoped Access
 * architecture end-to-end: one external organization, one external user
 * (Architect) genuinely restricted to exactly one of the demo company's 5
 * projects via project_users. Idempotent.
 */
class ExternalUserDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::find(15);

        if (! $company || $company->industry !== 'construction') {
            $this->command?->warn('Skipping ExternalUserDemoSeeder: demo construction company (id 15) not found.');

            return;
        }

        if (User::withoutGlobalScope('company')->where('email', 'architect@nairobidesign.example')->exists()) {
            $this->command?->info('ExternalUserDemoSeeder: demo data already exists, skipping.');

            return;
        }

        $org = ExternalOrganization::create([
            'company_id' => 15,
            'name' => 'Nairobi Design Consultants',
            'type' => 'Architect',
            'contact_person' => 'Wanjiru Kamau',
            'phone' => '+254712345001',
            'email' => 'info@nairobidesign.example',
            'status' => 'Active',
        ]);

        $project = Project::withoutGlobalScope('company')->where('company_id', 15)->first();

        $externalUser = User::create([
            'company_id' => 15,
            'user_type' => 'external',
            'external_organization_id' => $org->id,
            'name' => 'Wanjiru Kamau',
            'email' => 'architect@nairobidesign.example',
            'password' => 'password',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $externalUser->assignRole('Architect');

        ProjectUser::create([
            'company_id' => 15,
            'user_id' => $externalUser->id,
            'project_id' => $project->id,
            'role' => 'Architect',
            'status' => 'Active',
            'start_date' => now()->subMonth(),
            'can_view_drawings' => true,
            'can_download_documents' => true,
            'can_upload_documents' => true,
            'can_comment' => true,
            'can_approve' => true,
            'can_view_financials' => false,
        ]);

        $this->command?->info("ExternalUserDemoSeeder: {$externalUser->email} created, restricted to project \"{$project->name}\".");
    }
}
