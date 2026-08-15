<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ComplianceRecord;
use App\Models\Company;
use App\Models\CorrectiveAction;
use App\Models\EmergencyItem;
use App\Models\Hazard;
use App\Models\MedicalCase;
use App\Models\NearMiss;
use App\Models\PpeIssuance;
use App\Models\Project;
use App\Models\RiskAssessment;
use App\Models\SafetyIncident;
use App\Models\SafetyInspection;
use App\Models\SafetyMeeting;
use App\Models\Site;
use App\Models\Staff;
use App\Models\ToolboxTalk;
use App\Models\TrainingRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SafetyDemoSeeder extends Seeder
{
    /**
     * Seeds realistic EHS demo data for the Construction showcase account
     * (Company #15). Idempotent. Tops up Projects/Sites to at least 4 if the
     * company only has minimal manually-created test data, so EHS records
     * spread across multiple sites as a real deployment would.
     */
    public function run(): void
    {
        $company = Company::find(15);

        if (! $company || $company->industry !== 'construction') {
            $this->command?->warn('SafetyDemoSeeder: Company #15 not found or not a construction company, skipping.');

            return;
        }

        if (NearMiss::where('company_id', $company->id)->exists()) {
            $this->command?->info('SafetyDemoSeeder: demo data already present, skipping.');

            return;
        }

        DB::transaction(function () use ($company) {
            $sites = $this->ensureSites($company);
            $this->seedData($company, $sites);
        });
    }

    private function ensureSites(Company $company)
    {
        $companyId = $company->id;
        $sites = Site::where('company_id', $companyId)->get();

        if ($sites->count() >= 4) {
            return $sites;
        }

        $client = Client::where('company_id', $companyId)->first() ?? Client::create([
            'company_id' => $companyId,
            'company_name' => 'Savannah Properties Ltd',
            'contact_person' => 'Grace Wanjiru',
            'phone' => '+254700900100',
            'email' => 'procurement@savannahproperties.example',
            'status' => 'Active',
        ]);

        $projectDefs = [
            ['name' => 'Riverside Apartments', 'lat' => -1.2841, 'lng' => 36.8155],
            ['name' => 'Westlands Office Tower', 'lat' => -1.2673, 'lng' => 36.8072],
            ['name' => 'Karen Villas Phase 2', 'lat' => -1.3192, 'lng' => 36.7076],
            ['name' => 'Thika Road Warehouse', 'lat' => -1.2189, 'lng' => 36.8875],
        ];

        foreach ($projectDefs as $def) {
            $project = Project::create([
                'company_id' => $companyId,
                'client_id' => $client->id,
                'name' => $def['name'],
                'site_address' => $def['name'] . ', Nairobi',
                'status' => 'Active',
                'start_date' => now()->subMonths(random_int(3, 12)),
                'end_date' => now()->addMonths(random_int(3, 10)),
                'budget' => random_int(20000000, 120000000),
                'contract_value' => random_int(25000000, 145000000),
                'progress_percent' => random_int(15, 80),
            ]);

            $sites->push(Site::create([
                'company_id' => $companyId,
                'project_id' => $project->id,
                'name' => $def['name'] . ' — Main Site',
                'location' => $def['name'] . ', Nairobi',
                'gps_lat' => $def['lat'],
                'gps_lng' => $def['lng'],
                'status' => 'Active',
            ]));
        }

        return $sites;
    }

    private function seedData(Company $company, $sites): void
    {
        $companyId = $company->id;
        $seed = 800000 + $companyId;
        $projects = Project::where('company_id', $companyId)->get();

        // ---- EHS Staff pool ----
        $staffDefs = [
            ['name' => 'Dennis Kiprotich', 'role' => 'Safety Officer'],
            ['name' => 'Agnes Nduta', 'role' => 'Safety Officer'],
            ['name' => 'Patrick Mwaura', 'role' => 'Site Manager'],
            ['name' => 'Rose Achieng', 'role' => 'Site Manager'],
            ['name' => 'Vincent Odhiambo', 'role' => 'HSE Inspector'],
            ['name' => 'Immaculate Wanjiku', 'role' => 'HSE Inspector'],
            ['name' => 'Collins Barasa', 'role' => 'HR Manager'],
            ['name' => 'Diana Chepkoech', 'role' => 'Site Engineer'],
        ];

        $staff = collect();
        foreach ($staffDefs as $i => $def) {
            $staff->push(Staff::create([
                'company_id' => $companyId,
                'full_name' => $def['name'],
                'phone_number' => '+254704' . str_pad((string) ($seed + $i), 6, '0', STR_PAD_LEFT),
                'email' => strtolower(str_replace(' ', '.', $def['name'])) . '@tanakihub.example',
                'employment_type' => 'Permanent',
                'date_hired' => now()->subMonths(random_int(6, 36)),
                'status' => 'Active',
            ]));
        }

        [$safetyOfficer1, $safetyOfficer2, $siteMgr1, $siteMgr2, $inspector1, $inspector2, $hrManager, $engineer] = $staff->all();

        // ---- Safety Incidents ----
        $incidentDefs = [
            ['category' => 'Fall From Height', 'severity' => 'High', 'injury' => 'Lost Time Injury', 'status' => 'Under Investigation', 'days' => 3],
            ['category' => 'Slip / Trip', 'severity' => 'Low', 'injury' => 'First Aid Case', 'status' => 'Closed', 'days' => 14],
            ['category' => 'Struck By Object', 'severity' => 'Medium', 'injury' => 'Medical Treatment Case', 'status' => 'Corrective Action', 'days' => 7],
            ['category' => 'Electrical', 'severity' => 'Critical', 'injury' => 'Lost Time Injury', 'status' => 'Reported', 'days' => 1],
            ['category' => 'Manual Handling', 'severity' => 'Low', 'injury' => 'First Aid Case', 'status' => 'Closed', 'days' => 21],
            ['category' => 'Equipment Accident', 'severity' => 'High', 'injury' => 'Medical Treatment Case', 'status' => 'Closed', 'days' => 30],
        ];

        $incidents = collect();
        foreach ($incidentDefs as $i => $def) {
            $site = $sites[$i % $sites->count()];
            $incidents->push(SafetyIncident::create([
                'company_id' => $companyId,
                'date' => now()->subDays($def['days']),
                'time' => '10:30',
                'project_id' => $site->project_id,
                'site_id' => $site->id,
                'location' => 'Block ' . chr(65 + ($i % 3)),
                'reported_by_id' => $i % 2 === 0 ? $siteMgr1->id : $siteMgr2->id,
                'injured_person_name' => 'Site Worker ' . ($i + 1),
                'employment_type' => 'Staff',
                'incident_category' => $def['category'],
                'severity' => $def['severity'],
                'injury_classification' => $def['injury'],
                'description' => "{$def['category']} incident during routine site works.",
                'witnesses' => 'Two crew members present at the time.',
                'immediate_response' => 'Site secured, first aid administered, supervisor notified.',
                'medical_treatment' => $def['injury'] === 'First Aid Case' ? 'First Aid' : 'Medical Treatment',
                'investigation_status' => $def['status'] === 'Closed' ? 'Completed' : ($def['status'] === 'Reported' ? 'Not Started' : 'In Progress'),
                'root_cause' => $def['status'] === 'Closed' ? 'Inadequate control measures identified and corrected.' : null,
                'status' => $def['status'],
                'approved_by_id' => $def['status'] === 'Closed' ? $safetyOfficer1->id : null,
                'approved_at' => $def['status'] === 'Closed' ? now()->subDays($def['days'] - 1) : null,
                'closed_by_id' => $def['status'] === 'Closed' ? $safetyOfficer1->id : null,
                'closed_at' => $def['status'] === 'Closed' ? now()->subDays($def['days'] - 1) : null,
            ]));
        }

        // ---- Near Misses ----
        $nearMissDefs = [
            ['desc' => 'Scaffolding plank found loose on level 3, corrected before use.', 'risk' => 'High'],
            ['desc' => 'Forklift reversed near pedestrian walkway without spotter.', 'risk' => 'Medium'],
            ['desc' => 'Unsecured load nearly fell from delivery truck.', 'risk' => 'High'],
            ['desc' => 'Wet floor near site office caused near-slip.', 'risk' => 'Low'],
        ];
        foreach ($nearMissDefs as $i => $def) {
            $site = $sites[$i % $sites->count()];
            NearMiss::create([
                'company_id' => $companyId,
                'date' => now()->subDays(random_int(1, 20)),
                'project_id' => $site->project_id,
                'site_id' => $site->id,
                'description' => $def['desc'],
                'potential_consequence' => 'Could have resulted in serious injury if uncorrected.',
                'risk_level' => $def['risk'],
                'reported_by_id' => $i % 2 === 0 ? $inspector1->id : $inspector2->id,
                'corrective_action' => 'Hazard corrected on the spot and area re-inspected.',
                'status' => $i === 0 ? 'Under Review' : 'Closed',
            ]);
        }

        // ---- Hazard Register ----
        $hazardDefs = [
            ['type' => 'Fall From Height', 'likelihood' => 'Likely', 'impact' => 'Major'],
            ['type' => 'Electrical', 'likelihood' => 'Possible', 'impact' => 'Catastrophic'],
            ['type' => 'Excavation', 'likelihood' => 'Possible', 'impact' => 'Major'],
            ['type' => 'Moving Machinery', 'likelihood' => 'Likely', 'impact' => 'Moderate'],
            ['type' => 'Dust / Airborne Particles', 'likelihood' => 'Almost Certain', 'impact' => 'Minor'],
            ['type' => 'Scaffolding', 'likelihood' => 'Unlikely', 'impact' => 'Major'],
        ];
        $hazards = collect();
        foreach ($hazardDefs as $i => $def) {
            $site = $sites[$i % $sites->count()];
            $hazards->push(Hazard::create([
                'company_id' => $companyId,
                'project_id' => $site->project_id,
                'site_id' => $site->id,
                'hazard_type' => $def['type'],
                'description' => $def['type'] . ' hazard identified during routine site walkaround.',
                'likelihood' => $def['likelihood'],
                'impact' => $def['impact'],
                'existing_controls' => 'Signage, PPE requirement, toolbox talk briefing.',
                'recommended_controls' => 'Install additional barriers and increase inspection frequency.',
                'responsible_staff_id' => $i % 2 === 0 ? $siteMgr1->id : $siteMgr2->id,
                'target_date' => now()->addDays(random_int(7, 45)),
                'status' => $i % 3 === 0 ? 'Closed' : ($i % 3 === 1 ? 'In Progress' : 'Open'),
            ]));
        }

        // ---- Risk Assessments ----
        $raDefs = [
            ['activity' => 'Working at Height — Roof Installation', 'l' => 4, 's' => 5, 'rl' => 2, 'rs' => 4],
            ['activity' => 'Excavation Works — Foundation Trench', 'l' => 3, 's' => 4, 'rl' => 2, 'rs' => 3],
            ['activity' => 'Crane Lifting Operations', 'l' => 3, 's' => 5, 'rl' => 1, 'rs' => 4],
            ['activity' => 'Electrical Installation — Main Panel', 'l' => 2, 's' => 5, 'rl' => 1, 'rs' => 3],
        ];
        foreach ($raDefs as $i => $def) {
            $site = $sites[$i % $sites->count()];
            RiskAssessment::create([
                'company_id' => $companyId,
                'project_id' => $site->project_id,
                'site_id' => $site->id,
                'activity' => $def['activity'],
                'persons_at_risk' => 'Site workers, subcontractors, visitors within the work zone.',
                'hazards' => 'Falls, dropped objects, electrocution, struck-by hazards.',
                'likelihood' => $def['l'],
                'severity' => $def['s'],
                'control_measures' => 'Permit to work, PPE, exclusion zones, competent supervision, toolbox talk before start.',
                'residual_likelihood' => $def['rl'],
                'residual_severity' => $def['rs'],
                'assessed_by_id' => $safetyOfficer1->id,
                'approved_by_id' => $i < 2 ? $safetyOfficer2->id : null,
                'approved_at' => $i < 2 ? now()->subDays(5) : null,
                'review_date' => now()->addMonths(6),
                'status' => $i < 2 ? 'Approved' : 'Pending Approval',
            ]);
        }

        // ---- Safety Inspections ----
        $inspectionTypes = ['Daily Site Inspection', 'Scaffold Inspection', 'Fire Inspection', 'PPE Inspection', 'Crane Inspection', 'Electrical Inspection'];
        foreach ($sites as $i => $site) {
            $type = $inspectionTypes[$i % count($inspectionTypes)];
            $inspection = SafetyInspection::create([
                'company_id' => $companyId,
                'inspection_type' => $type,
                'project_id' => $site->project_id,
                'site_id' => $site->id,
                'inspector_id' => $i % 2 === 0 ? $inspector1->id : $inspector2->id,
                'date' => now()->subDays(random_int(0, 14)),
                'result' => $i % 5 === 0 ? 'Needs Review' : 'Pass',
                'score' => random_int(85, 100),
                'status' => $i % 4 === 0 ? 'Draft' : 'Approved',
                'approved_by_id' => $i % 4 !== 0 ? $safetyOfficer1->id : null,
                'approved_at' => $i % 4 !== 0 ? now()->subDays(1) : null,
            ]);

            $inspection->items()->createMany([
                ['item_text' => 'PPE worn correctly by all workers', 'status' => 'Pass', 'sort_order' => 1],
                ['item_text' => 'Housekeeping / walkways clear', 'status' => 'Pass', 'sort_order' => 2],
                ['item_text' => 'Signage and barricades in place', 'status' => $i % 5 === 0 ? 'Fail' : 'Pass', 'sort_order' => 3],
            ]);
        }

        // ---- Toolbox Talks ----
        $talkTopics = ['Working at Height Safety', 'Manual Handling Techniques', 'Fire Prevention on Site', 'Electrical Safety Awareness', 'PPE Compliance'];
        foreach ($talkTopics as $i => $topic) {
            $site = $sites[$i % $sites->count()];
            $talk = ToolboxTalk::create([
                'company_id' => $companyId,
                'topic' => $topic,
                'presenter_id' => $i % 2 === 0 ? $safetyOfficer1->id : $safetyOfficer2->id,
                'project_id' => $site->project_id,
                'site_id' => $site->id,
                'date' => now()->subDays($i * 4),
                'training_materials' => 'Key hazards, control measures, and reporting procedures reviewed with the crew.',
                'comments' => 'Good engagement from the crew; no questions outstanding.',
            ]);

            foreach ($staff->random(min(4, $staff->count())) as $attendee) {
                $talk->attendees()->create(['staff_id' => $attendee->id, 'attended' => true]);
            }
        }

        // ---- Safety Meetings ----
        foreach (range(1, 3) as $i) {
            $site = $sites[($i - 1) % $sites->count()];
            $meeting = SafetyMeeting::create([
                'company_id' => $companyId,
                'site_id' => $site->id,
                'title' => 'Monthly Safety Committee Meeting — ' . now()->subMonths($i - 1)->format('F Y'),
                'date' => now()->subMonths($i - 1)->startOfMonth()->addDays(4),
                'agenda' => "1. Incident review\n2. Hazard register updates\n3. Training compliance\n4. Corrective action follow-up",
                'minutes' => 'Committee reviewed open incidents and hazards. Agreed to increase inspection frequency on active sites.',
                'attendees_count' => 6,
            ]);

            $meeting->attendees()->createMany(
                $staff->take(6)->map(fn ($s) => ['staff_id' => $s->id])->toArray()
            );

            $meeting->actionItems()->create([
                'description' => 'Close out overdue corrective actions from last inspection cycle.',
                'responsible_staff_id' => $safetyOfficer1->id,
                'due_date' => now()->addDays(14),
                'status' => 'Open',
            ]);
        }

        // ---- Corrective Actions ----
        $correctiveDefs = [
            ['desc' => 'Install additional guardrails on Block A scaffolding.', 'source' => $hazards[0], 'sourceKey' => 'hazard_id', 'priority' => 'High', 'days' => -3, 'status' => 'Open'],
            ['desc' => 'Repair damaged electrical panel cover.', 'source' => $hazards[1], 'sourceKey' => 'hazard_id', 'priority' => 'Critical', 'days' => -1, 'status' => 'In Progress'],
            ['desc' => 'Retrain crane operators on lifting plan procedure.', 'source' => $incidents[3], 'sourceKey' => 'safety_incident_id', 'priority' => 'High', 'days' => 5, 'status' => 'In Progress'],
            ['desc' => 'Replace worn signage at excavation perimeter.', 'source' => $hazards[2], 'sourceKey' => 'hazard_id', 'priority' => 'Medium', 'days' => 10, 'status' => 'Completed'],
            ['desc' => 'Review and update manual handling toolbox talk content.', 'source' => $incidents[4], 'sourceKey' => 'safety_incident_id', 'priority' => 'Low', 'days' => 20, 'status' => 'Closed'],
        ];
        foreach ($correctiveDefs as $i => $def) {
            $data = [
                'company_id' => $companyId,
                'description' => $def['desc'],
                'responsible_staff_id' => $i % 2 === 0 ? $siteMgr1->id : $siteMgr2->id,
                'priority' => $def['priority'],
                'due_date' => now()->addDays($def['days']),
                'progress_percent' => match ($def['status']) {
                    'Open' => 0, 'In Progress' => 50, 'Completed' => 90, 'Verified' => 100, 'Closed' => 100, default => 0,
                },
                'status' => $def['status'],
                $def['sourceKey'] => $def['source']->id,
            ];

            if (in_array($def['status'], ['Completed', 'Verified', 'Closed'], true)) {
                $data['evidence'] = 'Photographic evidence and sign-off attached.';
            }
            if (in_array($def['status'], ['Verified', 'Closed'], true)) {
                $data['verified_by_id'] = $safetyOfficer1->id;
                $data['verified_at'] = now()->subDays(2);
            }
            if ($def['status'] === 'Closed') {
                $data['closure_approved_by_id'] = $safetyOfficer2->id;
                $data['closure_approved_at'] = now()->subDay();
            }

            CorrectiveAction::create($data);
        }

        // ---- Emergency Management Register ----
        $emergencyDefs = [
            ['type' => 'Emergency Contact', 'name' => 'Nairobi Fire Brigade', 'details' => ['Phone' => '999 / +254 20 2222181']],
            ['type' => 'Emergency Contact', 'name' => "St. Mary's Mission Hospital", 'details' => ['Phone' => '+254 20 2811000']],
            ['type' => 'Response Plan', 'name' => 'Fire Evacuation Plan — All Sites', 'details' => ['Scenario' => 'Fire outbreak', 'Reviewed' => now()->subMonths(2)->format('d M Y')]],
            ['type' => 'Assembly Point', 'name' => 'Main Gate Assembly Point', 'details' => ['Capacity' => '150 persons']],
            ['type' => 'Fire Equipment', 'name' => 'Extinguisher FE-014 — Site Office', 'details' => ['Type' => 'CO2', 'Last Serviced' => now()->subMonths(4)->format('d M Y')]],
            ['type' => 'First Aid Kit', 'name' => 'First Aid Kit — Block A Store', 'details' => ['Contents' => 'Standard OSHA kit, restocked monthly']],
            ['type' => 'Drill', 'name' => 'Q3 Fire Evacuation Drill', 'details' => ['Participants' => '48', 'Duration' => '12 minutes']],
            ['type' => 'Team Member', 'name' => 'Fire Warden — Block A', 'details' => ['Contact' => $siteMgr1->phone_number]],
        ];
        foreach ($emergencyDefs as $i => $def) {
            $site = $sites[$i % $sites->count()];
            EmergencyItem::create([
                'company_id' => $companyId,
                'type' => $def['type'],
                'name' => $def['name'],
                'project_id' => $site->project_id,
                'site_id' => $site->id,
                'details' => $def['details'],
                'responsible_staff_id' => $safetyOfficer1->id,
                'status' => 'Active',
                'last_checked_at' => now()->subMonths(random_int(1, 4)),
                'next_check_date' => $def['type'] === 'Drill' ? now()->addMonths(3) : now()->addMonths(6),
            ]);
        }

        // ---- PPE Compliance ----
        $ppeItems = ['Helmet', 'Boots', 'Gloves', 'Harness', 'Eye Protection', 'Reflective Vest'];
        foreach ($staff as $i => $member) {
            foreach (array_slice($ppeItems, 0, 3) as $j => $item) {
                PpeIssuance::create([
                    'company_id' => $companyId,
                    'staff_id' => $member->id,
                    'item' => $item,
                    'size' => ['S', 'M', 'L', 'XL'][($i + $j) % 4],
                    'cost' => random_int(800, 4500),
                    'issue_date' => now()->subMonths(random_int(1, 10)),
                    'replacement_due_date' => now()->addDays(($i + $j) % 4 === 0 ? -5 : (($i + $j) % 3 === 0 ? 10 : 180)),
                    'status' => 'Issued',
                ]);
            }
        }

        // ---- Medical Cases ----
        MedicalCase::create([
            'company_id' => $companyId,
            'safety_incident_id' => $incidents[0]->id,
            'staff_id' => $siteMgr1->id,
            'visit_date' => now()->subDays(3),
            'hospital' => "St. Mary's Mission Hospital",
            'doctor' => 'Dr. Ochieng',
            'diagnosis' => 'Fractured wrist from fall.',
            'treatment' => 'Cast applied, pain management prescribed.',
            'return_to_work_date' => now()->addDays(25),
            'medical_restrictions' => 'Light duty only, no lifting over 5kg.',
            'medical_clearance' => 'Restricted',
            'status' => 'Under Treatment',
        ]);
        MedicalCase::create([
            'company_id' => $companyId,
            'safety_incident_id' => $incidents[2]->id,
            'staff_id' => $engineer->id,
            'visit_date' => now()->subDays(7),
            'hospital' => 'Nairobi West Hospital',
            'doctor' => 'Dr. Wambui',
            'diagnosis' => 'Minor concussion from falling object.',
            'treatment' => 'Observation, released same day.',
            'return_to_work_date' => now()->subDays(4),
            'medical_clearance' => 'Cleared',
            'status' => 'Closed',
        ]);

        // ---- Safety Training ----
        $safetyTrainingTypes = ['Safety Induction', 'Working at Height', 'Fire Safety', 'First Aid', 'Confined Space', 'Scaffold Safety'];
        foreach ($staff as $i => $member) {
            foreach (array_slice($safetyTrainingTypes, 0, 3) as $j => $type) {
                TrainingRecord::create([
                    'company_id' => $companyId,
                    'staff_id' => $member->id,
                    'training_type' => $type,
                    'title' => $type . ' Certification',
                    'provider' => 'NITA Kenya',
                    'date' => now()->subMonths(random_int(2, 18)),
                    'expiry_date' => now()->addDays(($i + $j) % 5 === 0 ? 15 : (($i + $j) % 4 === 0 ? -10 : 300)),
                    'status' => ($i + $j) % 4 === 0 ? 'Expired' : 'Completed',
                ]);
            }
        }

        // ---- Compliance Records ----
        $complianceDefs = [
            ['type' => 'Safety Audit', 'title' => 'Annual OSH Compliance Audit', 'status' => 'Compliant', 'days' => 300],
            ['type' => 'Regulatory Inspection', 'title' => 'DOSH Workplace Inspection', 'status' => 'Compliant', 'days' => 250],
            ['type' => 'Permit Compliance', 'title' => 'NEMA Environmental Permit Renewal', 'status' => 'Expiring Soon', 'days' => 20],
            ['type' => 'Site Safety Plan', 'title' => 'Site Safety Plan — Riverside Apartments', 'status' => 'Compliant', 'days' => 180],
            ['type' => 'Insurance Compliance', 'title' => 'WIBA Workmen Compensation Cover', 'status' => 'Compliant', 'days' => 200],
            ['type' => 'Legal Compliance', 'title' => 'OSHA Registration Certificate', 'status' => 'Non-Compliant', 'days' => -15],
        ];
        foreach ($complianceDefs as $i => $def) {
            $site = $sites[$i % $sites->count()];
            ComplianceRecord::create([
                'company_id' => $companyId,
                'type' => $def['type'],
                'title' => $def['title'],
                'project_id' => $site->project_id,
                'site_id' => $site->id,
                'reference_number' => 'REF-' . ($seed + $i),
                'issuing_authority' => ['DOSH', 'NEMA', 'NCA', 'WIBA'][$i % 4],
                'responsible_staff_id' => $safetyOfficer1->id,
                'issue_date' => now()->subMonths(6),
                'expiry_date' => now()->addDays($def['days']),
                'status' => $def['status'],
            ]);
        }

        $this->command?->info('SafetyDemoSeeder: EHS demo data seeded for Company #' . $companyId);
    }
}
