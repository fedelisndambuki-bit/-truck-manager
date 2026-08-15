<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $roles = [
            'Super Admin',
            'Company Admin',
            'Fleet Manager',
            'Accountant',
            'Driver',
            // Construction industry roles
            'Managing Director',
            'Operations Director',
            'Construction Manager',
            'Project Manager',
            'Site Engineer',
            'Site Supervisor',
            'Quantity Surveyor',
            'Procurement Officer',
            'Safety Officer',
            'Finance Officer',
            'HR Officer',
            'Equipment Manager',
            'Store Manager',
            'Administrator',
            'Site Manager',
            'Workshop Manager',
            // Labour & HR Management roles
            'HR Manager',
            'Payroll Officer',
            'Department Manager',
            // Materials Management roles
            'Warehouse Officer',
            // Subcontractor Management roles
            'Contract Manager',
            // Project Finance module roles
            'Finance Director',
            'Finance Manager',
            // Enterprise Finance & Cost Accounting module roles
            'Chief Financial Officer',
            'Chief Accountant',
            'Accounts Payable Officer',
            'Accounts Receivable Officer',
            'Cashier',
            'Budget Officer',
            'Branch Accountant',
            'Project Accountant',
            // CRM, Sales & Contract Management module roles
            'Commercial Director',
            'Sales Manager',
            'Sales Representative',
            'Account Manager',
            'Customer Service Manager',
            'Customer Service Officer',
            'Collections Officer',
            // Document Management module roles
            'Document Controller',
            'Consultant',
            'Client Representative',
            // Reports & Business Intelligence module roles
            'Board Member',
            'Construction Director',
            // Workflow & Approvals (Core Platform) module roles
            'Workflow Administrator',
            'Auditor',
            // External User & Project-Scoped Access (Core Platform) — Portal Roles
            'Architect',
            'Structural Engineer',
            'MEP Engineer',
            'Clerk of Works',
            'Supplier',
            'Subcontractor',
            // Quarry Dashboard (Quarry & Mining Industry) module roles
            'Quarry Manager',
            'Production Manager',
            'Maintenance Manager',
            'Commercial Manager',
            'Dispatch Supervisor',
            'Weighbridge Operator',
            'Environmental Officer',
            // Production Management module roles
            'Plant Manager',
            'Shift Supervisor',
            'Production Clerk',
            // Blast Management module roles
            'Blast Engineer',
            // Crushing Plant Management module roles
            'Control Room Operator',
            // Stockpile Management module roles
            'Inventory Controller',
            'Quality Manager',
            // Smart Weighbridge & Loading Management module roles
            'Dispatch Manager',
            'Weighbridge Supervisor',
            'Loader Supervisor',
            'Loader Operator',
            'Security Officer',
            // Dispatch Management module roles
            'Dispatch Officer',
            // Laboratory & Quality Control module roles
            'Laboratory Manager',
            'Laboratory Technician',
            // Equipment & Asset Management module roles
            'Maintenance Technician',
            'Maintenance Planner',
            // Procurement & Supply Chain Management module roles
            'Procurement Director',
            'Procurement Manager',
            'Warehouse Manager',
            'Storekeeper',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Create AI permissions
        $permissions = [
            'access-ai',
            'view-ai-logs',
            'delete-ai-logs',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign permissions to roles
        $superAdmin = Role::findByName('Super Admin');
        $companyAdmin = Role::findByName('Company Admin');
        $fleetManager = Role::findByName('Fleet Manager');

        // Super Admin has all permissions
        $superAdmin->givePermissionTo($permissions);

        // Company Admin can access AI and view logs
        $companyAdmin->givePermissionTo(['access-ai', 'view-ai-logs']);

        // Fleet Manager can only access AI
        $fleetManager->givePermissionTo(['access-ai']);

        // --- Projects module permission matrix ---
        $projectPermissions = [
            'projects.view',
            'projects.create',
            'projects.edit',
            'projects.approve',
            'projects.archive',
            'projects.delete',
            'projects.export',
            'projects.manage',
        ];

        foreach ($projectPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $fullAccessRoles = ['Managing Director', 'Operations Director', 'Construction Manager', 'Administrator'];
        foreach ($fullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($projectPermissions);
        }

        Role::findByName('Project Manager')->givePermissionTo([
            'projects.view', 'projects.create', 'projects.edit', 'projects.export', 'projects.manage',
        ]);

        Role::findByName('Quantity Surveyor')->givePermissionTo([
            'projects.view', 'projects.edit', 'projects.export',
        ]);

        Role::findByName('Finance Officer')->givePermissionTo([
            'projects.view', 'projects.approve', 'projects.export',
        ]);

        Role::findByName('Site Engineer')->givePermissionTo([
            'projects.view', 'projects.edit',
        ]);

        $viewOnlyRoles = ['Safety Officer', 'Procurement Officer', 'Store Manager', 'HR Officer'];
        foreach ($viewOnlyRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['projects.view']);
        }

        // --- Sites module permission matrix ---
        $sitePermissions = [
            'sites.view',
            'sites.create',
            'sites.edit',
            'sites.approve',
            'sites.delete',
            'sites.export',
            'sites.assign_resources',
            'sites.manage_inspections',
            'sites.manage_safety',
            'sites.manage_documents',
            'sites.manage_reports',
        ];

        foreach ($sitePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($fullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($sitePermissions);
        }

        $siteManagementRoles = ['Project Manager', 'Site Manager'];
        foreach ($siteManagementRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'sites.view', 'sites.create', 'sites.edit', 'sites.approve', 'sites.export',
                'sites.assign_resources', 'sites.manage_inspections', 'sites.manage_safety',
                'sites.manage_documents', 'sites.manage_reports',
            ]);
        }

        Role::findByName('Site Engineer')->givePermissionTo([
            'sites.view', 'sites.edit', 'sites.assign_resources', 'sites.manage_inspections', 'sites.manage_documents',
        ]);

        Role::findByName('Site Supervisor')->givePermissionTo([
            'sites.view', 'sites.edit', 'sites.assign_resources', 'sites.manage_inspections',
        ]);

        Role::findByName('Safety Officer')->givePermissionTo([
            'sites.view', 'sites.manage_safety',
        ]);

        Role::findByName('Store Manager')->givePermissionTo([
            'sites.view', 'sites.assign_resources',
        ]);

        $siteViewOnlyRoles = ['Finance Officer', 'HR Officer'];
        foreach ($siteViewOnlyRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['sites.view']);
        }

        // --- Equipment Allocation module permission matrix ---
        $equipmentPermissions = [
            'equipment.view',
            'equipment.create',
            'equipment.edit',
            'equipment.approve',
            'equipment.delete',
            'equipment.export',
            'equipment.transfer',
            'equipment.manage',
        ];

        foreach ($equipmentPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($fullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($equipmentPermissions);
        }

        $fleetEquipmentRoles = ['Fleet Manager', 'Equipment Manager'];
        foreach ($fleetEquipmentRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'equipment.view', 'equipment.create', 'equipment.edit', 'equipment.export',
                'equipment.transfer', 'equipment.manage',
            ]);
        }

        Role::findByName('Workshop Manager')->givePermissionTo([
            'equipment.view', 'equipment.manage',
        ]);

        $equipmentAllocatorRoles = ['Project Manager', 'Site Manager'];
        foreach ($equipmentAllocatorRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'equipment.view', 'equipment.create', 'equipment.approve', 'equipment.export',
            ]);
        }

        Role::findByName('Site Supervisor')->givePermissionTo([
            'equipment.view', 'equipment.edit',
        ]);

        // --- Labour & HR Management module permission matrix ---
        $hrPermissions = [
            'hr.view',
            'hr.create',
            'hr.edit',
            'hr.delete',
            'hr.approve',
            'hr.export',
            'hr.payroll_access',
            'hr.recruitment_access',
            'hr.leave_approval',
            'hr.training_management',
            'hr.safety_management',
            'hr.attendance_access',
        ];

        foreach ($hrPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $hrFullAccessRoles = ['Managing Director', 'Operations Director', 'Administrator', 'HR Manager'];
        foreach ($hrFullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($hrPermissions);
        }

        Role::findByName('HR Officer')->givePermissionTo([
            'hr.view', 'hr.create', 'hr.edit', 'hr.export',
            'hr.recruitment_access', 'hr.leave_approval', 'hr.training_management', 'hr.attendance_access',
        ]);

        Role::findByName('Payroll Officer')->givePermissionTo([
            'hr.view', 'hr.export', 'hr.payroll_access',
        ]);

        Role::findByName('Department Manager')->givePermissionTo([
            'hr.view', 'hr.edit', 'hr.approve', 'hr.attendance_access',
        ]);

        Role::findByName('Safety Officer')->givePermissionTo([
            'hr.view', 'hr.safety_management',
        ]);

        $hrSupervisoryRoles = ['Site Supervisor', 'Site Manager', 'Project Manager', 'Construction Manager'];
        foreach ($hrSupervisoryRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'hr.view', 'hr.approve', 'hr.attendance_access',
            ]);
        }

        Role::findByName('Finance Officer')->givePermissionTo([
            'hr.view', 'hr.payroll_access', 'hr.export',
        ]);

        // --- Materials Management module permission matrix ---
        $materialsPermissions = [
            'materials.view',
            'materials.create',
            'materials.edit',
            'materials.delete',
            'materials.approve',
            'materials.issue',
            'materials.transfer',
            'materials.adjust',
            'materials.count',
            'materials.export',
            'materials.putaway',
        ];

        foreach ($materialsPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($fullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($materialsPermissions);
        }

        Role::findByName('Procurement Officer')->givePermissionTo([
            'materials.view', 'materials.create', 'materials.edit', 'materials.approve', 'materials.export',
        ]);

        $warehouseOperationsRoles = ['Store Manager', 'Warehouse Officer'];
        foreach ($warehouseOperationsRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'materials.view', 'materials.issue', 'materials.transfer', 'materials.adjust', 'materials.count',
            ]);
        }

        $materialsViewExportRoles = ['Quantity Surveyor', 'Finance Officer'];
        foreach ($materialsViewExportRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'materials.view', 'materials.export',
            ]);
        }

        $materialsRequesterRoles = ['Project Manager', 'Site Manager'];
        foreach ($materialsRequesterRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'materials.view', 'materials.create', 'materials.approve',
            ]);
        }

        $materialsIssueRoles = ['Site Engineer', 'Site Supervisor'];
        foreach ($materialsIssueRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'materials.view', 'materials.create', 'materials.issue',
            ]);
        }

        // Warehouse Manager / Storekeeper (Procurement roles) and Inventory
        // Controller (Stockpile role) previously held zero materials.*
        // permissions despite being the roles the Warehouse & Inventory
        // module spec names explicitly — this closes that gap.
        $warehouseFullOperationsRoles = ['Warehouse Manager', 'Storekeeper'];
        foreach ($warehouseFullOperationsRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($materialsPermissions);
        }

        Role::findByName('Inventory Controller')->givePermissionTo([
            'materials.view', 'materials.transfer', 'materials.adjust', 'materials.count', 'materials.putaway',
        ]);

        // Matches Auditor's existing <module>.view/<module>.export-only scoping.
        Role::findByName('Auditor')->givePermissionTo(['materials.view', 'materials.export']);

        // --- Subcontractor Management module permission matrix ---
        $subcontractorPermissions = [
            'subcontractors.view',
            'subcontractors.create',
            'subcontractors.edit',
            'subcontractors.approve',
            'subcontractors.award',
            'subcontractors.certify',
            'subcontractors.evaluate',
            'subcontractors.delete',
            'subcontractors.export',
        ];

        foreach ($subcontractorPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($fullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($subcontractorPermissions);
        }

        Role::findByName('Contract Manager')->givePermissionTo([
            'subcontractors.view', 'subcontractors.create', 'subcontractors.edit',
            'subcontractors.award', 'subcontractors.certify', 'subcontractors.export',
        ]);

        Role::findByName('Procurement Officer')->givePermissionTo([
            'subcontractors.view', 'subcontractors.create', 'subcontractors.approve',
        ]);

        Role::findByName('Quantity Surveyor')->givePermissionTo([
            'subcontractors.view', 'subcontractors.edit', 'subcontractors.export',
        ]);

        Role::findByName('Finance Officer')->givePermissionTo([
            'subcontractors.view', 'subcontractors.certify', 'subcontractors.export',
        ]);

        Role::findByName('Site Manager')->givePermissionTo([
            'subcontractors.view', 'subcontractors.create',
        ]);

        // --- Daily Site Diary module permission matrix ---
        $siteDiaryPermissions = [
            'site_diaries.view',
            'site_diaries.create',
            'site_diaries.edit',
            'site_diaries.submit',
            'site_diaries.review',
            'site_diaries.approve',
            'site_diaries.reject',
            'site_diaries.lock',
            'site_diaries.export',
            'site_diaries.manage_settings',
        ];

        foreach ($siteDiaryPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($fullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($siteDiaryPermissions);
        }

        $diaryManagementRoles = ['Project Manager', 'Site Manager'];
        foreach ($diaryManagementRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'site_diaries.view', 'site_diaries.create', 'site_diaries.edit', 'site_diaries.submit',
                'site_diaries.review', 'site_diaries.approve', 'site_diaries.export',
            ]);
        }

        $diaryFieldRoles = ['Site Engineer', 'Site Supervisor'];
        foreach ($diaryFieldRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'site_diaries.view', 'site_diaries.create', 'site_diaries.edit', 'site_diaries.submit',
            ]);
        }

        $diaryViewOnlyRoles = ['Safety Officer', 'Finance Officer', 'HR Officer'];
        foreach ($diaryViewOnlyRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['site_diaries.view']);
        }

        Role::findByName('Quantity Surveyor')->givePermissionTo([
            'site_diaries.view', 'site_diaries.export',
        ]);

        // --- Safety & Incident Management module permission matrix ---
        $safetyPermissions = [
            'safety.view',
            'safety.create',
            'safety.edit',
            'safety.delete',
            'safety.investigate',
            'safety.close',
            'safety.approve',
            'safety.export',
            'safety.manage',
        ];

        foreach ($safetyPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($fullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($safetyPermissions);
        }

        Role::findByName('Safety Officer')->givePermissionTo($safetyPermissions);

        $safetyManagementRoles = ['Site Manager', 'Project Manager', 'HR Manager'];
        foreach ($safetyManagementRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'safety.view', 'safety.create', 'safety.edit', 'safety.investigate', 'safety.close', 'safety.approve', 'safety.export',
            ]);
        }

        $safetyFieldRoles = ['Site Engineer', 'Site Supervisor'];
        foreach ($safetyFieldRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'safety.view', 'safety.create', 'safety.edit',
            ]);
        }

        $safetyViewOnlyRoles = ['Finance Officer', 'Procurement Officer', 'Store Manager', 'Equipment Manager', 'Contract Manager'];
        foreach ($safetyViewOnlyRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['safety.view']);
        }

        // --- Project Finance module permission matrix ---
        $financePermissions = [
            'project_finance.view',
            'project_finance.create',
            'project_finance.edit',
            'project_finance.approve',
            'project_finance.delete',
            'project_finance.export',
            'project_finance.manage',
        ];

        foreach ($financePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($fullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($financePermissions);
        }

        $financeFullAccessRoles = ['Finance Director', 'Finance Manager', 'Finance Officer', 'Accountant'];
        foreach ($financeFullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($financePermissions);
        }

        $financeContributorRoles = ['Project Manager', 'Quantity Surveyor'];
        foreach ($financeContributorRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'project_finance.view', 'project_finance.create', 'project_finance.edit', 'project_finance.export',
            ]);
        }

        Role::findByName('Procurement Officer')->givePermissionTo([
            'project_finance.view',
        ]);

        // --- Enterprise Finance & Cost Accounting module permission matrix ---
        $enterpriseFinancePermissions = [
            'finance.chart_of_accounts',
            'finance.journal_entries',
            'finance.general_ledger',
            'finance.profit_centres',
            'finance.periods',
        ];

        foreach ($enterpriseFinancePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $enterpriseFinanceFullAccessRoles = [
            'Finance Director', 'Finance Manager', 'Chief Financial Officer', 'Chief Accountant', 'Accountant',
        ];
        foreach ($enterpriseFinanceFullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($enterpriseFinancePermissions);
        }

        Role::findByName('Finance Officer')->givePermissionTo([
            'finance.chart_of_accounts', 'finance.journal_entries', 'finance.general_ledger',
        ]);

        $enterpriseFinanceEntryRoles = ['Accounts Payable Officer', 'Accounts Receivable Officer', 'Branch Accountant', 'Project Accountant'];
        foreach ($enterpriseFinanceEntryRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['finance.journal_entries', 'finance.general_ledger']);
        }

        Role::findByName('Cashier')->givePermissionTo(['finance.general_ledger']);
        Role::findByName('Budget Officer')->givePermissionTo(['finance.general_ledger', 'finance.periods']);

        // Matches Auditor's existing workflow.view/workflow.export-only scoping — view/export only, never edit or post.
        Role::findByName('Auditor')->givePermissionTo(['finance.general_ledger']);

        // --- Document Management module permission matrix ---
        $documentPermissions = [
            'documents.view',
            'documents.create',
            'documents.edit',
            'documents.approve',
            'documents.archive',
            'documents.delete',
            'documents.export',
            'documents.manage',
            'documents.share',
        ];

        foreach ($documentPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($fullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($documentPermissions);
        }

        Role::findByName('Document Controller')->givePermissionTo($documentPermissions);

        $documentContributorRoles = ['Project Manager', 'Site Manager', 'Site Engineer'];
        foreach ($documentContributorRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'documents.view', 'documents.create', 'documents.edit', 'documents.export', 'documents.share',
            ]);
        }

        $documentViewOnlyRoles = ['Quantity Surveyor', 'Consultant', 'Client Representative'];
        foreach ($documentViewOnlyRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'documents.view',
            ]);
        }

        // --- Reports & Business Intelligence module permission matrix ---
        $biPermissions = [
            'bi.view',
            'bi.create',
            'bi.edit',
            'bi.delete',
            'bi.export',
            'bi.manage',
            'bi.schedule',
        ];

        foreach ($biPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($fullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($biPermissions);
        }

        $biFullAccessRoles = ['Board Member', 'Construction Director', 'Finance Director', 'Operations Director'];
        foreach ($biFullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($biPermissions);
        }

        Role::findByName('Administrator')->givePermissionTo($biPermissions);

        $biContributorRoles = ['Project Manager', 'Site Manager', 'HR Manager', 'Quantity Surveyor'];
        foreach ($biContributorRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'bi.view', 'bi.create', 'bi.edit', 'bi.export',
            ]);
        }

        // The Custom Report Builder now has Finance/CRM/Warehouse data
        // sources (see BusinessIntelligenceService::dataSourceDefinition())
        // — give the roles that own those modules view/export access, same
        // scope as $biContributorRoles.
        $biCrossModuleViewRoles = ['Chief Financial Officer', 'Finance Manager', 'Warehouse Manager', 'Commercial Director', 'Sales Manager'];
        foreach ($biCrossModuleViewRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['bi.view', 'bi.export']);
        }

        // --- Workflow & Approvals (Core Platform) permission matrix ---
        // Collapsed to 4 permissions to match how every other module in this
        // app actually enforces access today (HasModuleAccess gates a whole
        // Resource/Page on one slug — no per-action Policy classes exist).
        $workflowPermissions = [
            'workflow.view',
            'workflow.manage',
            'workflow.approve',
            'workflow.export',
        ];

        foreach ($workflowPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($fullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($workflowPermissions);
        }

        $workflowFullAccessRoles = ['Workflow Administrator', 'Managing Director', 'Operations Director', 'Department Manager'];
        foreach ($workflowFullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($workflowPermissions);
        }

        Role::findByName('Administrator')->givePermissionTo($workflowPermissions);

        Role::findByName('Auditor')->givePermissionTo(['workflow.view', 'workflow.export']);

        // Baseline: every other role can view and act as an approver on
        // something — Workflow & Approvals is Core, used platform-wide.
        // Looked up defensively (some legacy role names exist under a
        // non-default guard from unrelated seeders and would otherwise throw).
        foreach ($roles as $roleName) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

            if (! $role || $role->hasPermissionTo('workflow.manage') || $role->hasPermissionTo('workflow.view')) {
                continue;
            }

            $role->givePermissionTo(['workflow.view', 'workflow.approve']);
        }

        // --- External User & Project-Scoped Access (Core Platform) ---
        // platform.assigned_projects_only is the single blanket flag
        // User::isProjectRestricted() checks for internal staff who should
        // also be scoped to their assigned projects only (external users are
        // restricted automatically via user_type, independent of this grant).
        Permission::firstOrCreate(['name' => 'platform.assigned_projects_only']);

        // Portal Roles — narrow, real module grants only (no permission slug
        // is invented here that isn't already wired to an existing Resource's
        // getModuleSlug(); Quality Control has no permission gate anywhere in
        // the app today, so no role — new or old — can be scoped in/out of it
        // yet, a pre-existing gap, not something this pass retrofits).
        $architectStyleRoles = ['Architect', 'Structural Engineer', 'MEP Engineer'];
        foreach ($architectStyleRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'projects.view', 'sites.view', 'documents.view', 'documents.create',
                'workflow.view', 'workflow.approve', 'platform.assigned_projects_only',
            ]);
        }

        Role::findByName('Clerk of Works')->givePermissionTo([
            'projects.view', 'sites.view', 'documents.view', 'safety.view',
            'workflow.view', 'workflow.approve', 'platform.assigned_projects_only',
        ]);

        Role::findByName('Supplier')->givePermissionTo([
            'materials.view', 'documents.view', 'platform.assigned_projects_only',
        ]);

        Role::findByName('Subcontractor')->givePermissionTo([
            'projects.view', 'documents.view', 'safety.view',
            'workflow.view', 'workflow.approve', 'platform.assigned_projects_only',
        ]);

        // Extend the two portal-shaped roles from the EDMS module with the
        // same project-scoping flag plus the additional modules a client/
        // consultant collaborator should reasonably see.
        Role::findByName('Consultant')->givePermissionTo([
            'projects.view', 'sites.view', 'workflow.view', 'workflow.approve', 'platform.assigned_projects_only',
        ]);

        Role::findByName('Client Representative')->givePermissionTo([
            'project_finance.view', 'bi.view', 'platform.assigned_projects_only',
        ]);

        // --- Quarry Dashboard (Quarry & Mining Industry) permission matrix ---
        // Only the newly-built Resources actually check a module slug
        // (CrushingPlantResource, StockpileResource, WeighbridgeTicketResource,
        // ProductionLogResource) — Equipment/Maintenance/FuelLog/ExtractionSite
        // remain industry-gated only, matching their existing app-wide
        // convention of no per-module permission slug.
        $quarryPermissions = [
            'production.view', 'production.create', 'production.edit', 'production.delete', 'production.export', 'production.manage',
            'crushing.view', 'crushing.create', 'crushing.edit', 'crushing.manage',
            'stockpile.view', 'stockpile.create', 'stockpile.edit', 'stockpile.manage', 'stockpile.export',
            'weighbridge.view', 'weighbridge.create', 'weighbridge.edit', 'weighbridge.manage', 'weighbridge.export',
            'dispatch.view', 'dispatch.create', 'dispatch.edit', 'dispatch.manage', 'dispatch.export',
            'lab.view', 'lab.create', 'lab.edit', 'lab.manage', 'lab.export',
        ];

        foreach ($quarryPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        Role::findByName('Quarry Manager')->givePermissionTo($quarryPermissions);

        Role::findByName('Production Manager')->givePermissionTo([
            'production.view', 'production.create', 'production.edit', 'production.export', 'production.manage',
            'crushing.view', 'crushing.edit', 'stockpile.view',
        ]);

        Role::findByName('Maintenance Manager')->givePermissionTo([
            'production.view', 'crushing.view',
        ]);

        Role::findByName('Commercial Manager')->givePermissionTo([
            'weighbridge.view', 'stockpile.view', 'stockpile.create', 'production.view', 'production.export',
        ]);

        Role::findByName('Dispatch Supervisor')->givePermissionTo([
            'weighbridge.view', 'weighbridge.create', 'weighbridge.edit', 'weighbridge.manage',
            'stockpile.view', 'stockpile.create',
        ]);

        Role::findByName('Weighbridge Operator')->givePermissionTo([
            'weighbridge.view', 'weighbridge.create', 'weighbridge.edit',
        ]);

        // Environmental Officer is seeded now (matching the role's spot in the
        // wider org chart) even though the Environmental module itself is
        // deferred — no permission slug exists to grant yet.
        Role::findByName('Environmental Officer')->givePermissionTo([
            'production.view',
        ]);

        // --- Production Management module roles ---
        // Reuses the existing production.*/crushing.* slugs seeded above —
        // no new permission group is invented per sub-entity (Quarry Faces,
        // Pits, Production Lines, Planning, Downtime, Targets, Stock
        // Transfers all gate on the single production.view slug already
        // wired to every new Resource this pass).
        Role::findByName('Plant Manager')->givePermissionTo([
            'production.view', 'production.edit', 'crushing.view', 'crushing.edit', 'crushing.manage',
        ]);

        Role::findByName('Shift Supervisor')->givePermissionTo([
            'production.view', 'production.create', 'production.edit',
        ]);

        Role::findByName('Production Clerk')->givePermissionTo([
            'production.view', 'production.create',
        ]);

        // Managing Director / Operations Director / Administrator already have
        // full access to every other module (bi.*, workflow.*, hr.*) — extend
        // that same full-access convention to Production Management.
        foreach (['Managing Director', 'Operations Director', 'Administrator'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo($quarryPermissions);
        }

        // --- Blast Management module ---
        // One slug per Resource-cluster (matching production.*/workflow.*
        // precedent), not one slug per sub-entity (Drilling, Explosives,
        // Safety Zones, Execution, Post-Blast, Fragmentation, Environmental,
        // Compliance, Documents all gate on the single blast.view slug).
        $blastPermissions = ['blast.view', 'blast.create', 'blast.edit', 'blast.export', 'blast.manage'];

        foreach ($blastPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach (['Managing Director', 'Operations Director', 'Administrator', 'Quarry Manager'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo($blastPermissions);
        }

        Role::findByName('Blast Engineer')->givePermissionTo(['blast.view', 'blast.create', 'blast.edit']);

        // Safety Officer / Environmental Officer are real approvers on the
        // Blast Plan Approval workflow (role-resolved by the Workflow
        // Engine, independent of this CRUD permission) — they only need
        // view access to the module itself.
        foreach (['Safety Officer', 'Environmental Officer', 'Production Manager', 'Store Manager'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['blast.view']);
        }

        // --- Crushing Plant Management module ---
        // One slug per Resource-cluster (matching production.*/blast.*
        // precedent), not one slug per sub-entity (Plants, Production Lines,
        // Crushers, Screens, Conveyors, Feeders, Plant Operations, Downtime,
        // Product Output, Energy Monitoring all gate on the single
        // plant.view slug).
        $plantPermissions = ['plant.view', 'plant.create', 'plant.edit', 'plant.export', 'plant.manage'];

        foreach ($plantPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach (['Managing Director', 'Operations Director', 'Administrator', 'Quarry Manager'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo($plantPermissions);
        }

        Role::findByName('Plant Manager')->givePermissionTo([
            'plant.view', 'plant.create', 'plant.edit', 'plant.manage',
        ]);

        foreach (['Maintenance Manager', 'Shift Supervisor', 'Control Room Operator'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['plant.view', 'plant.create']);
        }

        Role::findByName('Production Manager')->givePermissionTo(['plant.view']);

        // --- Stockpile Management module ---
        // stockpile.* (including the newly-added stockpile.export) is already
        // granted in full to Managing Director/Operations Director/
        // Administrator/Quarry Manager via $quarryPermissions above — only the
        // module-specific roles need their own grants here.
        Role::findByName('Inventory Controller')->givePermissionTo([
            'stockpile.view', 'stockpile.create', 'stockpile.edit', 'stockpile.manage',
        ]);

        Role::findByName('Quality Manager')->givePermissionTo([
            'stockpile.view',
        ]);

        // --- Smart Weighbridge & Loading Management module ---
        // weighbridge.* (including the newly-added weighbridge.export) is
        // already granted in full to Managing Director/Operations Director/
        // Administrator/Quarry Manager via $quarryPermissions above — only
        // the module-specific roles need their own grants here.
        foreach (['Dispatch Manager', 'Weighbridge Supervisor'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'weighbridge.view', 'weighbridge.create', 'weighbridge.edit', 'weighbridge.manage',
            ]);
        }

        foreach (['Weighbridge Operator', 'Loader Operator', 'Security Officer'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['weighbridge.view', 'weighbridge.create']);
        }

        Role::findByName('Loader Supervisor')->givePermissionTo([
            'weighbridge.view', 'weighbridge.create', 'weighbridge.edit',
        ]);

        Role::findByName('Finance Manager')->givePermissionTo(['weighbridge.view']);

        // --- Dispatch Management module ---
        // dispatch.* (including dispatch.export) is already granted in full
        // to Managing Director/Operations Director/Administrator/Quarry
        // Manager via $quarryPermissions above — only the module-specific
        // roles need their own grants here.
        Role::findByName('Dispatch Manager')->givePermissionTo([
            'dispatch.view', 'dispatch.create', 'dispatch.edit', 'dispatch.manage', 'dispatch.export',
        ]);

        Role::findByName('Dispatch Officer')->givePermissionTo([
            'dispatch.view', 'dispatch.create', 'dispatch.edit',
        ]);

        foreach (['Commercial Manager', 'Fleet Manager', 'Finance Manager'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['dispatch.view']);
        }

        // Row-level scoping (a driver only seeing their own assigned
        // dispatches) is deferred — matching every prior module's lack of
        // row-level security infra. This is view access to the module only.
        Role::findByName('Driver')->givePermissionTo(['dispatch.view']);

        // --- Laboratory & Quality Control module ---
        // lab.* (including lab.export) is already granted in full to
        // Managing Director/Operations Director/Administrator/Quarry Manager
        // via $quarryPermissions above — only the module-specific roles need
        // their own grants here. Quality Manager (already existed as a
        // Stockpile Management role) becomes this module's natural co-owner
        // alongside the new Laboratory Manager role, and is the bootstrap-
        // safe approver role for the dispatch-quality-hold-approval workflow.
        foreach (['Laboratory Manager', 'Quality Manager'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo([
                'lab.view', 'lab.create', 'lab.edit', 'lab.manage', 'lab.export',
            ]);
        }

        Role::findByName('Laboratory Technician')->givePermissionTo([
            'lab.view', 'lab.create', 'lab.edit',
        ]);

        foreach (['Production Manager', 'Dispatch Manager', 'Commercial Manager'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['lab.view']);
        }

        // Matches Auditor's existing workflow.view/workflow.export-only scoping.
        Role::findByName('Auditor')->givePermissionTo(['lab.view', 'lab.export']);

        // --- Equipment & Asset Management module ---
        // One slug per Resource-cluster (matching production.*/blast.*/
        // plant.*/lab.* precedent), not one slug per sub-entity (Register,
        // Categories, Assignments, Utilization, Health, Inspections, PM,
        // Breakdowns, Work Orders, Workshop, Service History, Spare Parts,
        // Tyres, Attachments, Fuel, Telematics, Costs, Depreciation,
        // Warranty, Documents all gate on the single eam.view slug).
        $eamPermissions = ['eam.view', 'eam.create', 'eam.edit', 'eam.export', 'eam.manage'];

        foreach ($eamPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach (['Managing Director', 'Operations Director', 'Administrator', 'Quarry Manager'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo($eamPermissions);
        }

        Role::findByName('Maintenance Manager')->givePermissionTo([
            'eam.view', 'eam.create', 'eam.edit', 'eam.export', 'eam.manage',
        ]);

        foreach (['Workshop Manager', 'Equipment Manager'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['eam.view', 'eam.create', 'eam.edit']);
        }

        Role::findByName('Maintenance Technician')->givePermissionTo(['eam.view', 'eam.create']);

        Role::findByName('Maintenance Planner')->givePermissionTo(['eam.view', 'eam.create', 'eam.edit']);

        Role::findByName('Fleet Manager')->givePermissionTo(['eam.view']);

        Role::findByName('Inventory Controller')->givePermissionTo(['eam.view', 'eam.create']);

        Role::findByName('Finance Manager')->givePermissionTo(['eam.view']);

        // --- Procurement & Supply Chain Management module ---
        // One slug per Resource-cluster (matching eam.*/plant.* precedent),
        // not one slug per sub-entity (Purchase Planning, Requisitions, RFQs,
        // Quotations, Purchase Orders, Blanket POs, Contracts, Goods
        // Receiving, Returns, Supplier Management, Budgets, Reports all gate
        // on the single procurement.view slug).
        $procurementPermissions = ['procurement.view', 'procurement.create', 'procurement.edit', 'procurement.export', 'procurement.manage'];

        foreach ($procurementPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach (['Managing Director', 'Administrator'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo($procurementPermissions);
        }

        Role::findByName('Procurement Director')->givePermissionTo($procurementPermissions);

        Role::findByName('Procurement Manager')->givePermissionTo([
            'procurement.view', 'procurement.create', 'procurement.edit', 'procurement.export', 'procurement.manage',
        ]);

        Role::findByName('Procurement Officer')->givePermissionTo([
            'procurement.view', 'procurement.create', 'procurement.edit',
        ]);

        foreach (['Warehouse Manager', 'Storekeeper'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['procurement.view', 'procurement.create']);
        }

        Role::findByName('Department Manager')->givePermissionTo(['procurement.view', 'procurement.create']);

        Role::findByName('Finance Manager')->givePermissionTo(['procurement.view', 'procurement.export']);

        foreach (['Project Manager', 'Workshop Manager', 'Quarry Manager'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['procurement.view', 'procurement.create']);
        }

        // Extend the existing thin Supplier portal role (materials.view,
        // documents.view, platform.assigned_projects_only) rather than
        // creating a second "Supplier"-named role.
        Role::findByName('Supplier')->givePermissionTo(['procurement.view', 'procurement.create']);

        // --- CRM, Sales & Contract Management module permission matrix ---
        $crmPermissions = [
            'crm.view', 'crm.create', 'crm.edit', 'crm.delete', 'crm.approve', 'crm.convert', 'crm.export',
        ];

        foreach ($crmPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        foreach ($fullAccessRoles as $roleName) {
            Role::findByName($roleName)->givePermissionTo($crmPermissions);
        }

        foreach (['Commercial Director', 'Sales Manager'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo($crmPermissions);
        }

        foreach (['Sales Representative', 'Account Manager'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['crm.view', 'crm.create', 'crm.edit', 'crm.convert']);
        }

        // Customer Service and Collections roles are named by the spec for
        // modules this phase doesn't build yet (tickets, payment promises)
        // — view-only on the CRM core keeps them functional without write
        // access to a domain they don't operate.
        foreach (['Customer Service Manager', 'Customer Service Officer', 'Collections Officer'] as $roleName) {
            Role::findByName($roleName)->givePermissionTo(['crm.view']);
        }

        // Matches Auditor's existing <module>.view/<module>.export-only scoping.
        Role::findByName('Auditor')->givePermissionTo(['crm.view', 'crm.export']);

        // --- Fleet Management (Trucking Industry) module permission matrix ---
        // TruckResource, CargoResource and ClientResource gate on
        // 'fleet-management'; TripResource gates on 'trip-tracking';
        // SettlementResource gates on 'payroll'; EmployeeResource,
        // DriverEmergencyAlertResource and DriverChatMessageResource gate on
        // 'driver-management'. All four slugs were referenced by
        // getModuleSlug() but never actually seeded, so $user->can()
        // silently returned false for every role not already
        // blanket-bypassed by Gate::before (Super Admin/Company Admin/Fleet
        // Manager/Accountant) — and no other role could ever be granted
        // access without hitting Spatie's PermissionDoesNotExist.
        $fleetPermissions = ['fleet-management', 'trip-tracking', 'payroll', 'driver-management'];

        foreach ($fleetPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Fleet Manager/Company Admin/Accountant already bypass every
        // permission check via Gate::before — these grants are for
        // defense-in-depth (matching every other module's explicit-grant
        // convention), so the roles show correctly in the Filament role
        // editor, and so DriverNotificationRecipients (SOS alerts) can
        // actually resolve a real recipient list via can('driver-management').
        Role::findByName('Fleet Manager')->givePermissionTo(['fleet-management', 'trip-tracking', 'driver-management']);
        Role::findByName('Company Admin')->givePermissionTo($fleetPermissions);
        Role::findByName('Accountant')->givePermissionTo(['payroll']);
    }
}
