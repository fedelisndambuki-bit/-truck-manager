<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\DigitalSignature;
use App\Models\DocumentFolder;
use App\Models\Drawing;
use App\Models\EdmsDocument;
use App\Models\MethodStatement;
use App\Models\Project;
use App\Models\RiskAssessment;
use App\Models\Rfi;
use App\Models\Staff;
use App\Models\Subcontractor;
use App\Models\TechnicalSubmittal;
use App\Models\Transmittal;
use App\Services\Construction\DocumentManagementService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Realistic Document Management demo data for the existing construction demo
 * company/projects (see ConstructionDemoSeeder / ProjectFinanceDemoSeeder).
 * Idempotent — bails if folders already exist for this company.
 */
class DocumentManagementDemoSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::find(15);

        if (! $company || $company->industry !== 'construction') {
            $this->command?->warn('Skipping DocumentManagementDemoSeeder: demo construction company (id 15) not found.');

            return;
        }

        if (DocumentFolder::withoutGlobalScope('company')->where('company_id', 15)->exists()) {
            $this->command?->info('DocumentManagementDemoSeeder: data already exists, skipping.');

            return;
        }

        DB::transaction(function () use ($company) {
            $companyId = $company->id;
            $service = app(DocumentManagementService::class);
            $staffId = Staff::withoutGlobalScope('company')->where('company_id', $companyId)->first()?->id;
            $subcontractorId = Subcontractor::withoutGlobalScope('company')->where('company_id', $companyId)->first()?->id;
            $riskAssessmentId = RiskAssessment::withoutGlobalScope('company')->where('company_id', $companyId)->first()?->id;

            $projects = Project::withoutGlobalScope('company')->where('company_id', $companyId)->whereNotNull('contract_value')->get();

            foreach ($projects as $project) {
                // Folder structure
                $rootFolder = DocumentFolder::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'project_id' => $project->id,
                    'name' => $project->name,
                ]);

                $drawingsFolder = DocumentFolder::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'parent_id' => $rootFolder->id,
                    'project_id' => $project->id,
                    'name' => 'Drawings',
                ]);

                $contractsFolder = DocumentFolder::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'parent_id' => $rootFolder->id,
                    'project_id' => $project->id,
                    'name' => 'Contracts',
                ]);

                // Documents across categories
                $documentDefs = [
                    ['category' => 'Contracts', 'title' => 'Main Contract Agreement', 'folder' => $contractsFolder->id, 'status' => 'Approved', 'expiry' => now()->addMonths(4)],
                    ['category' => 'Contracts', 'title' => 'Site Access & Way-Leave Permit', 'folder' => $contractsFolder->id, 'status' => 'Approved', 'expiry' => now()->addDays(21)],
                    ['category' => 'BOQs', 'title' => 'Bill of Quantities — Rev A', 'status' => 'Approved'],
                    ['category' => 'Meeting Minutes', 'title' => 'Weekly Site Meeting Minutes', 'status' => 'Approved'],
                    ['category' => 'Site Photos', 'title' => 'Foundation Progress Photo', 'status' => 'Approved'],
                    ['category' => 'Safety Plans', 'title' => 'Site Safety Management Plan', 'status' => 'Approved'],
                    ['category' => 'Inspection Reports', 'title' => 'Concrete Pour Inspection Report', 'status' => 'Under Review'],
                    ['category' => 'Invoices', 'title' => 'Supplier Invoice — Steel Delivery', 'status' => 'Approved'],
                    ['category' => 'Structural Drawings', 'title' => 'Foundation Layout Cover Sheet', 'folder' => $drawingsFolder->id, 'status' => 'Draft'],
                ];

                $documentsByTitle = [];

                foreach ($documentDefs as $def) {
                    $document = EdmsDocument::withoutGlobalScope('company')->create([
                        'company_id' => $companyId,
                        'title' => $def['title'],
                        'category' => $def['category'],
                        'project_id' => $project->id,
                        'folder_id' => $def['folder'] ?? null,
                        'owner_staff_id' => $staffId,
                        'status' => $def['status'],
                        'file_type' => 'pdf',
                        'preview_type' => 'pdf',
                        'expiry_date' => $def['expiry'] ?? null,
                    ]);

                    $service->createVersion($document, [
                        'file_path' => null,
                        'file_type' => 'pdf',
                        'change_notes' => 'Initial upload',
                    ]);

                    if ($def['status'] === 'Approved') {
                        DigitalSignature::withoutGlobalScope('company')->create([
                            'company_id' => $companyId,
                            'signable_type' => EdmsDocument::class,
                            'signable_id' => $document->id,
                            'signer_staff_id' => $staffId,
                            'signer_name' => Staff::withoutGlobalScope('company')->find($staffId)?->full_name ?? 'Project Manager',
                        ]);
                    }

                    $documentsByTitle[$def['title']] = $document;
                }

                // Put the draft drawing cover sheet through a live approval
                // workflow so the Reviews & Approvals inbox has real content.
                $service->startApprovalWorkflow(
                    $documentsByTitle['Foundation Layout Cover Sheet'],
                    ['Site Engineer', 'Project Manager', 'Construction Manager']
                );

                // Share the safety plan with the site's consulting engineer.
                $service->shareDocument($documentsByTitle['Site Safety Management Plan'], [
                    'shared_with_type' => 'consultant',
                    'shared_with_name' => 'Consulting Engineers Ltd',
                    'shared_with_email' => 'safety@consultingengineers.example',
                    'access_level' => 'view',
                ]);

                // Drawings — one with a superseded revision
                $drawing = Drawing::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'type' => 'Drawing',
                    'project_id' => $project->id,
                    'folder_id' => $drawingsFolder->id,
                    'discipline' => 'Structural',
                    'title' => 'Structural Framing Plan — Level 1',
                    'status' => 'Approved',
                ]);
                $service->createVersion($drawing, ['change_notes' => 'Initial issue']);
                $service->createVersion($drawing, ['change_notes' => 'Revised per structural engineer comments']);

                Drawing::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'type' => 'Drawing',
                    'project_id' => $project->id,
                    'discipline' => 'MEP',
                    'title' => 'Electrical Layout — Ground Floor',
                    'status' => 'Under Review',
                ]);

                // Shop drawing awaiting dual approval
                $shopDrawing = Drawing::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'type' => 'Shop Drawing',
                    'project_id' => $project->id,
                    'discipline' => 'Structural',
                    'title' => 'Precast Panel Shop Drawing',
                    'status' => 'Submitted',
                ]);
                $service->createVersion($shopDrawing, ['change_notes' => 'Initial submission']);
                $shopDrawing->update(['consultant_approved_at' => now()->subDays(2)]);

                // RFI with a response
                $rfi = Rfi::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'subject' => 'Clarification on rebar spacing at grid line C',
                    'project_id' => $project->id,
                    'raised_by_id' => $staffId,
                    'assigned_to_id' => $staffId,
                    'priority' => 'High',
                    'due_date' => now()->addDays(5),
                    'status' => 'Open',
                    'description' => 'Drawing S-102 shows conflicting rebar spacing versus the specification. Please confirm correct spacing.',
                ]);

                // Method statement linked to an existing risk assessment
                $methodStatement = MethodStatement::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'title' => 'Method Statement — Excavation Works',
                    'project_id' => $project->id,
                    'risk_assessment_id' => $riskAssessmentId,
                    'related_activities' => 'Excavation, shoring, spoil removal',
                    'status' => 'Approved',
                ]);
                $service->createVersion($methodStatement, ['change_notes' => 'Initial approved version']);

                // Technical submittal
                TechnicalSubmittal::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'title' => 'Ready-Mix Concrete Mix Design Submittal',
                    'type' => 'Material',
                    'project_id' => $project->id,
                    'subcontractor_id' => $subcontractorId,
                    'status' => 'Submitted',
                ]);

                // Transmittal distributing the approved drawing
                $transmittal = Transmittal::withoutGlobalScope('company')->create([
                    'company_id' => $companyId,
                    'project_id' => $project->id,
                    'sender_id' => $staffId,
                    'recipient_name' => 'Consulting Engineers Ltd',
                    'recipient_email' => 'drawings@consultingengineers.example',
                    'purpose' => 'For Approval',
                    'delivery_method' => 'Email',
                    'comments' => 'Please review and return comments within 5 working days.',
                ]);
                $transmittal->items()->create([
                    'company_id' => $companyId,
                    'drawing_id' => $drawing->id,
                ]);
            }
        });

        $this->command?->info('DocumentManagementDemoSeeder: seeded folders, documents, drawings, RFIs, method statements, submittals, transmittals and signatures.');
    }
}
