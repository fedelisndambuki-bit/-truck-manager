<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Client;
use App\Models\Company;
use App\Models\DailySiteDiary;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\EquipmentAllocation;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Labourer;
use App\Models\Material;
use App\Models\MaterialDelivery;
use App\Models\Payment;
use App\Models\Project;
use App\Models\QualityInspection;
use App\Models\SafetyIncident;
use App\Models\Site;
use App\Models\SiteInspection;
use App\Models\Staff;
use App\Models\Subcontractor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConstructionDemoSeeder extends Seeder
{
    /**
     * Seeds realistic demo data for the Construction module's showcase
     * account (Company #15 / construction@gmail.com). Idempotent: bails
     * out if the target company doesn't exist or already has projects.
     */
    public function run(): void
    {
        $company = Company::find(15);

        if (! $company || $company->industry !== 'construction') {
            $this->command?->warn('ConstructionDemoSeeder: Company #15 not found or not a construction company, skipping.');

            return;
        }

        if (Project::withoutGlobalScope('company')->where('company_id', $company->id)->exists()) {
            $this->command?->info('ConstructionDemoSeeder: demo data already present, skipping.');

            return;
        }

        DB::transaction(function () use ($company) {
            $this->seedData($company);
        });
    }

    private function seedData(Company $company): void
    {
        $companyId = $company->id;
        $seed = 700000 + $companyId; // unique-ish base to avoid clashing with other seeded phone/ID numbers

        // ---- Client ----
        $client = Client::create([
            'company_id' => $companyId,
            'company_name' => 'Savannah Properties Ltd',
            'contact_person' => 'Grace Wanjiru',
            'phone' => '+254700' . str_pad((string) $seed, 6, '0', STR_PAD_LEFT),
            'email' => 'procurement@savannahproperties.example',
            'billing_terms' => 'Net 30',
            'status' => 'Active',
            'address' => 'Westlands, Nairobi',
        ]);

        // ---- Site crew (site managers, PMs, inspectors, labourers) ----
        // Staff, not Employee — every FK below (project_manager_id,
        // site_manager_id, inspector_id, logged_by_id, reported_by_id, and
        // Labourer/Attendance.employee_id) is Staff-foreign-keyed.
        $roles = ['Foreman', 'Engineer', 'Contractor', 'General Worker'];
        $names = [
            'James Otieno', 'Peter Mwangi', 'Faith Achieng', 'Samuel Kiplagat', 'Lucy Nyambura',
            'Daniel Mutua', 'Mary Wambui', 'Brian Kiptoo', 'Esther Njeri', 'John Kamau',
            'Catherine Adhiambo', 'Kevin Omondi', 'Ann Chebet', 'Michael Ouma', 'Sarah Gathoni',
        ];

        $staff = collect();
        $staffRoles = [];
        foreach ($names as $i => $name) {
            $n = $i + 1;
            $staffRoles[$i] = $roles[$i % count($roles)];
            $staff->push(Staff::create([
                'company_id' => $companyId,
                'full_name' => $name,
                'phone_number' => '+254701' . str_pad((string) ($seed + $n), 6, '0', STR_PAD_LEFT),
                'email' => strtolower(str_replace(' ', '.', $name)) . '@sitecrew.example',
                'national_id' => (string) ($seed + $n),
                'employment_type' => 'Permanent',
                'status' => 'Active',
                'date_hired' => now()->subMonths(random_int(2, 24)),
                'emergency_contact_name' => 'Next of Kin ' . $n,
                'emergency_contact_phone' => '+254702' . str_pad((string) ($seed + $n), 6, '0', STR_PAD_LEFT),
                'basic_salary' => random_int(25000, 90000),
            ]));
        }

        $projectManager = $staff[0];
        $siteManagers = [$staff[1], $staff[2], $staff[3], $staff[4], $staff[5]];
        $inspectors = [$staff[6], $staff[7]];

        // ---- Projects ----
        $projectDefs = [
            ['name' => 'Riverside Apartments', 'status' => 'Active', 'progress' => 62, 'budget' => 45000000, 'contract' => 58000000, 'lat' => -1.2841, 'lng' => 36.8155],
            ['name' => 'Westlands Office Tower', 'status' => 'Active', 'progress' => 38, 'budget' => 120000000, 'contract' => 145000000, 'lat' => -1.2673, 'lng' => 36.8072],
            ['name' => 'Karen Villas Phase 2', 'status' => 'Delayed', 'progress' => 45, 'budget' => 32000000, 'contract' => 40000000, 'lat' => -1.3192, 'lng' => 36.7076],
            ['name' => 'Thika Road Warehouse', 'status' => 'Planning', 'progress' => 5, 'budget' => 20000000, 'contract' => 25000000, 'lat' => -1.2189, 'lng' => 36.8875],
            ['name' => 'Mombasa Road Retail Park', 'status' => 'Completed', 'progress' => 100, 'budget' => 60000000, 'contract' => 72000000, 'lat' => -1.3421, 'lng' => 36.8878],
        ];

        $projects = collect();
        foreach ($projectDefs as $i => $def) {
            $projects->push(Project::create([
                'company_id' => $companyId,
                'client_id' => $client->id,
                'project_manager_id' => $projectManager->id,
                'name' => $def['name'],
                'site_address' => $def['name'] . ', Nairobi',
                'status' => $def['status'],
                'start_date' => now()->subMonths(random_int(3, 14)),
                'end_date' => $def['status'] === 'Completed' ? now()->subDays(random_int(5, 40)) : now()->addMonths(random_int(2, 10)),
                'budget' => $def['budget'],
                'contract_value' => $def['contract'],
                'progress_percent' => $def['progress'],
            ]));
        }

        // ---- Sites (2 per project) ----
        $sites = collect();
        foreach ($projects as $i => $project) {
            $def = $projectDefs[$i];
            foreach (range(1, 2) as $n) {
                $sites->push(Site::create([
                    'company_id' => $companyId,
                    'project_id' => $project->id,
                    'name' => $project->name . ' — Block ' . $n,
                    'location' => $project->site_address,
                    'gps_lat' => $def['lat'] + ($n === 2 ? 0.003 : 0),
                    'gps_lng' => $def['lng'] + ($n === 2 ? 0.003 : 0),
                    'site_manager_id' => $siteManagers[($i + $n) % count($siteManagers)]->id,
                    'status' => $project->status === 'Completed' ? 'Completed' : 'Active',
                ]));
            }
        }

        // ---- Labourers + Attendance (last 7 days) ----
        foreach ($staff as $i => $member) {
            $site = $sites[$i % $sites->count()];
            Labourer::create([
                'company_id' => $companyId,
                'employee_id' => $member->id,
                'project_id' => $site->project_id,
                'site_id' => $site->id,
                'role_on_site' => $staffRoles[$i],
                'daily_rate' => random_int(1500, 6000),
            ]);

            foreach (range(0, 6) as $daysAgo) {
                $present = random_int(1, 10) > 1; // ~90% attendance
                Attendance::create([
                    'company_id' => $companyId,
                    'employee_id' => $member->id,
                    'site_id' => $site->id,
                    'date' => now()->subDays($daysAgo)->toDateString(),
                    'check_in_at' => $present ? now()->subDays($daysAgo)->setTime(7, random_int(0, 30)) : null,
                    'check_out_at' => $present ? now()->subDays($daysAgo)->setTime(17, random_int(0, 30)) : null,
                    'status' => $present ? 'Present' : 'Absent',
                ]);
            }
        }

        // ---- Equipment + Allocations ----
        $equipmentDefs = [
            ['name' => 'Excavator CAT-01', 'type' => 'Excavator', 'status' => 'In Use', 'rate' => 4200],
            ['name' => 'Bulldozer KOM-02', 'type' => 'Bulldozer', 'status' => 'In Use', 'rate' => 4800],
            ['name' => 'Tower Crane TC-14', 'type' => 'Crane', 'status' => 'In Use', 'rate' => 7000],
            ['name' => 'Wheel Loader WL-05', 'type' => 'Loader', 'status' => 'Available', 'rate' => 3000],
            ['name' => 'Grader GR-03', 'type' => 'Grader', 'status' => 'Under Maintenance', 'rate' => 3500],
            ['name' => 'Excavator CAT-02', 'type' => 'Excavator', 'status' => 'Available', 'rate' => 4200],
            ['name' => 'Concrete Mixer CM-08', 'type' => 'Other', 'status' => 'In Use', 'rate' => 1000],
            ['name' => 'Backhoe Loader BL-06', 'type' => 'Loader', 'status' => 'In Use', 'rate' => 2600],
        ];

        foreach ($equipmentDefs as $i => $def) {
            $equipment = Equipment::create([
                'company_id' => $companyId,
                'name' => $def['name'],
                'equipment_type' => $def['type'],
                'status' => $def['status'],
                'serial_number' => 'SN-' . ($seed + $i),
                'purchase_date' => now()->subYears(random_int(1, 5)),
                'hourly_rate' => $def['rate'],
            ]);

            if ($def['status'] !== 'Available') {
                $site = $sites[$i % $sites->count()];
                EquipmentAllocation::create([
                    'company_id' => $companyId,
                    'equipment_id' => $equipment->id,
                    'project_id' => $site->project_id,
                    'site_id' => $site->id,
                    'allocated_from' => now()->subDays(random_int(5, 60)),
                    'operating_hours_today' => $def['status'] === 'In Use' ? round(random_int(30, 90) / 10, 1) : 0,
                    'fuel_consumed_today' => $def['status'] === 'In Use' ? round(random_int(100, 400) / 10, 1) : 0,
                ]);
            }
        }

        // ---- Materials + Deliveries ----
        $materialDefs = [
            ['name' => 'Cement (50kg)', 'unit' => 'bags', 'cost' => 850, 'reorder' => 200, 'onHand' => 150],
            ['name' => 'Steel Rebar 12mm', 'unit' => 'tonnes', 'cost' => 95000, 'reorder' => 10, 'onHand' => 6],
            ['name' => 'River Sand', 'unit' => 'm3', 'cost' => 2200, 'reorder' => 50, 'onHand' => 80],
            ['name' => 'Ballast', 'unit' => 'm3', 'cost' => 2500, 'reorder' => 50, 'onHand' => 40],
            ['name' => 'Bricks', 'unit' => 'pieces', 'cost' => 18, 'reorder' => 5000, 'onHand' => 12000],
            ['name' => 'Roofing Sheets', 'unit' => 'pieces', 'cost' => 1200, 'reorder' => 100, 'onHand' => 60],
            ['name' => 'PVC Pipes 4in', 'unit' => 'pieces', 'cost' => 650, 'reorder' => 80, 'onHand' => 45],
            ['name' => 'Paint (20L)', 'unit' => 'bags', 'cost' => 4500, 'reorder' => 30, 'onHand' => 20],
        ];

        $materials = collect();
        foreach ($materialDefs as $def) {
            $materials->push(Material::create([
                'company_id' => $companyId,
                'name' => $def['name'],
                'unit' => $def['unit'],
                'unit_cost' => $def['cost'],
                'reorder_level' => $def['reorder'],
                'quantity_on_hand' => $def['onHand'],
            ]));
        }

        $suppliers = ['Bamburi Cement Ltd', 'Devki Steel Mills', 'Nairobi Quarries Co', 'Simba Roofing Suppliers'];
        foreach ($materials as $i => $material) {
            foreach (range(1, 2) as $n) {
                MaterialDelivery::create([
                    'company_id' => $companyId,
                    'material_id' => $material->id,
                    'site_id' => $sites[($i + $n) % $sites->count()]->id,
                    'supplier_name' => $suppliers[$i % count($suppliers)],
                    'quantity' => random_int(10, 200),
                    'delivered_at' => $n === 1 ? now() : now()->addDays(random_int(1, 10)),
                    'purchase_order_ref' => 'PO-' . ($seed + $i * 10 + $n),
                    'status' => $n === 1 ? 'Delivered' : 'Pending',
                ]);
            }
        }

        // ---- Subcontractors ----
        $subDefs = [
            ['name' => 'ElectroFit Contractors', 'trade' => 'Electrical'],
            ['name' => 'AquaFlow Plumbing Services', 'trade' => 'Plumbing'],
            ['name' => 'SteelFrame Welding Works', 'trade' => 'Structural Steel'],
            ['name' => 'GreenScape Landscaping', 'trade' => 'Landscaping'],
        ];
        foreach ($subDefs as $i => $def) {
            Subcontractor::create([
                'company_id' => $companyId,
                'site_id' => $sites[$i % $sites->count()]->id,
                'company_name' => $def['name'],
                'contact_person' => 'Contact ' . ($i + 1),
                'phone' => '+254703' . str_pad((string) ($seed + $i), 6, '0', STR_PAD_LEFT),
                'email' => strtolower(str_replace(' ', '', $def['name'])) . '@example.com',
                'trade_specialty' => $def['trade'],
                'contract_value' => random_int(800000, 4000000),
                'status' => 'Active',
            ]);
        }

        // ---- Site Inspections, Safety Incidents, Quality Inspections, Diaries ----
        foreach ($sites as $i => $site) {
            SiteInspection::create([
                'company_id' => $companyId,
                'site_id' => $site->id,
                'inspector_id' => $inspectors[$i % count($inspectors)]->id,
                'date' => now()->subDays(random_int(0, 10)),
                'type' => ['General', 'Structural', 'Electrical'][$i % 3],
                'result' => $i % 5 === 0 ? 'Needs Review' : 'Pass',
                'notes' => 'Routine inspection completed with no major issues found.',
            ]);

            QualityInspection::create([
                'company_id' => $companyId,
                'site_id' => $site->id,
                'inspector_id' => $inspectors[$i % count($inspectors)]->id,
                'date' => now()->subDays(random_int(0, 10)),
                'checklist_item' => 'Concrete curing standard',
                'result' => $i % 6 === 0 ? 'Fail' : 'Pass',
                'notes' => 'Checked against QA/QC checklist.',
            ]);

            foreach (range(0, 2) as $d) {
                DailySiteDiary::create([
                    'company_id' => $companyId,
                    'site_id' => $site->id,
                    'date' => now()->subDays($d)->toDateString(),
                    'weather' => ['Sunny', 'Cloudy', 'Light Rain'][$d % 3],
                    'workers_on_site' => random_int(5, 20),
                    'work_summary' => 'Continued works as per project schedule; no safety incidents reported.',
                    'logged_by_id' => $siteManagers[$i % count($siteManagers)]->id,
                ]);
            }
        }

        SafetyIncident::create([
            'company_id' => $companyId,
            'site_id' => $sites[0]->id,
            'reported_by_id' => $siteManagers[0]->id,
            'date' => now()->subDays(2),
            'severity' => 'Medium',
            'description' => 'Worker sustained a minor cut while handling rebar. First aid administered on site.',
            'status' => 'Closed',
        ]);
        SafetyIncident::create([
            'company_id' => $companyId,
            'site_id' => $sites[2]->id,
            'reported_by_id' => $siteManagers[1]->id,
            'date' => now()->subDays(5),
            'severity' => 'Low',
            'description' => 'Near-miss: scaffolding plank found loose, corrected immediately.',
            'status' => 'Closed',
        ]);
        SafetyIncident::create([
            'company_id' => $companyId,
            'site_id' => $sites[4]->id,
            'reported_by_id' => $siteManagers[2]->id,
            'date' => now()->subDay(),
            'severity' => 'High',
            'description' => 'Equipment malfunction on tower crane; operations paused pending inspection.',
            'status' => 'Investigating',
        ]);

        // ---- Documents ----
        $documentDefs = [
            ['title' => 'Riverside Apartments — Architectural Blueprint', 'category' => 'Blueprint', 'project' => 0],
            ['title' => 'Client Contract — Savannah Properties', 'category' => 'Contract', 'project' => 0],
            ['title' => 'NEMA Environmental Permit', 'category' => 'Permit', 'project' => 1],
            ['title' => 'Structural Inspection Report — Karen Villas', 'category' => 'Inspection Report', 'project' => 2],
            ['title' => 'Site Safety Audit Report', 'category' => 'Safety Report', 'project' => 1],
        ];
        foreach ($documentDefs as $def) {
            Document::create([
                'company_id' => $companyId,
                'project_id' => $projects[$def['project']]->id,
                'category' => $def['category'],
                'title' => $def['title'],
                'file_path' => 'construction-documents/placeholder.pdf',
                'disk' => 'public',
            ]);
        }

        // ---- Project-tagged finance: invoices, payments, expenses ----
        foreach ($projects->take(4) as $i => $project) {
            $invoice = Invoice::create([
                'company_id' => $companyId,
                'project_id' => $project->id,
                'invoice_number' => 'CINV-' . ($seed + $i),
                'client_id' => $client->id,
                'route_from' => 'N/A',
                'route_to' => 'N/A',
                'invoice_date' => now()->subDays(20),
                'due_date' => now()->addDays(10),
                'line_items' => [['description' => 'Progress billing — ' . $project->name, 'amount' => 1500000]],
                'subtotal' => 1500000,
                'tax' => 240000,
                'total_amount' => 1740000,
                'status' => $i === 0 ? 'paid' : ($i === 1 ? 'overdue' : 'sent'),
            ]);

            if ($invoice->status === 'paid') {
                Payment::create([
                    'company_id' => $companyId,
                    'project_id' => $project->id,
                    'client_id' => $client->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total_amount,
                    'payment_method' => 'Bank Transfer',
                    'status' => 'Paid',
                    'invoice_number' => $invoice->invoice_number . '-PMT',
                    'payment_date' => now()->subDays(5),
                ]);
            }

            Expense::create([
                'company_id' => $companyId,
                'project_id' => $project->id,
                'category' => 'Materials',
                'amount' => random_int(400000, 900000),
                'date' => now()->subDays(random_int(1, 15)),
                'payment_method' => 'Bank Transfer',
                'description' => 'Material procurement for ' . $project->name,
            ]);
        }

        $this->command?->info('ConstructionDemoSeeder: demo data seeded for Company #' . $companyId);
    }
}
