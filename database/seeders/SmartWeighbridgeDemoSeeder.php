<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\DispatchOrder;
use App\Models\DispatchOrderLine;
use App\Models\Equipment;
use App\Models\ExtractionSite;
use App\Models\LoadingBay;
use App\Models\Material;
use App\Models\ProofOfDelivery;
use App\Models\Staff;
use App\Models\Stockpile;
use App\Models\TransportCompany;
use App\Models\User;
use App\Models\Weighbridge;
use App\Models\WeighbridgeException;
use App\Services\WorkflowEngine;
use Illuminate\Database\Seeder;

/**
 * Extends the Quarry demo company (Rift Valley Quarries Ltd) with real Smart
 * Weighbridge & Loading Management data: 2 weighbridges, 2 loading bays
 * (linked to real demo stockpiles/loader equipment), a transport company,
 * and 3 full-lifecycle Dispatch Orders taken all the way through Checked-In
 * -> Tare (auto-generated Loading Instruction + QR) -> Accepted -> Loading
 * -> Completed -> Gross Weighed -> (auto-approved / real WorkflowEngine
 * escalation / reload-demonstrating) -> Exited, each producing a real
 * Invoice and real StockMovement rows; one dispatch demonstrates split-load
 * lines; one WeighbridgeException is taken through approval; Proof of
 * Delivery records are created for exited trucks. Idempotent.
 */
class SmartWeighbridgeDemoSeeder extends Seeder
{
    public function run(): void
    {
        $northSite = ExtractionSite::withoutGlobalScope('company')->where('name', 'like', '%North Pit%')->first();
        $southSite = ExtractionSite::withoutGlobalScope('company')->where('name', 'like', '%South Pit%')->first();

        if (! $northSite || ! $southSite) {
            $this->command?->warn('Skipping SmartWeighbridgeDemoSeeder: demo quarry sites not found (run QuarryDemoSeeder first).');

            return;
        }

        $companyId = $northSite->company_id;

        if (Weighbridge::withoutGlobalScope('company')->where('company_id', $companyId)->exists()) {
            $this->command?->info('SmartWeighbridgeDemoSeeder: demo data already exists, skipping.');

            return;
        }

        if (! User::withoutGlobalScope('company')->where('email', 'weighbridge.supervisor@riftvalleyquarries.example')->exists()) {
            $supervisorUser = User::create([
                'company_id' => $companyId, 'name' => 'Grace Wanjiku',
                'email' => 'weighbridge.supervisor@riftvalleyquarries.example', 'password' => 'password', 'email_verified_at' => now(),
            ]);
            $supervisorUser->assignRole('Weighbridge Supervisor');
        }

        $staff = Staff::withoutGlobalScope('company')->where('company_id', $companyId)->where('full_name', 'John Mwangi')->first();
        $loaderEquipment = Equipment::withoutGlobalScope('company')->where('company_id', $companyId)->where('equipment_type', 'Loader')->first();
        $pileA = Stockpile::withoutGlobalScope('company')->where('company_id', $companyId)->where('label', 'Stockyard A — Bay 1')->first();
        $pileB = Stockpile::withoutGlobalScope('company')->where('company_id', $companyId)->where('label', 'Stockyard A — Bay 3')->first();
        $client = Client::withoutGlobalScope('company')->where('company_id', $companyId)->first();
        $material = Material::withoutGlobalScope('company')->find($pileA->material_id);
        $materialB = Material::withoutGlobalScope('company')->find($pileB->material_id);

        // Master data
        $weighbridge1 = Weighbridge::create([
            'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'name' => 'Weighbridge 1',
            'location' => 'North Pit Main Gate', 'capacity_tonnes' => 60, 'scale_type' => 'Dual Direction',
            'status' => 'Online', 'calibration_due_date' => now()->addMonths(6), 'certification_expiry' => now()->addYear(),
        ]);
        Weighbridge::create([
            'company_id' => $companyId, 'extraction_site_id' => $southSite->id, 'name' => 'Weighbridge 2',
            'location' => 'South Pit Gate', 'capacity_tonnes' => 40, 'scale_type' => 'Outbound', 'status' => 'Online',
        ]);

        $bay1 = LoadingBay::create([
            'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'name' => 'Loading Bay 1',
            'stockpile_id' => $pileA->id, 'loader_equipment_id' => $loaderEquipment?->id, 'supervisor_id' => $staff?->id,
            'max_queue' => 5, 'status' => 'Available',
        ]);
        LoadingBay::create([
            'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'name' => 'Loading Bay 2',
            'stockpile_id' => $pileB->id, 'max_queue' => 5, 'status' => 'Available',
        ]);

        $transportCo = TransportCompany::create([
            'company_id' => $companyId, 'name' => 'Speedway Transporters Ltd', 'contact_person' => 'Daniel Kiprono',
            'phone' => '+254712000301', 'status' => 'Active',
        ]);

        $engine = app(WorkflowEngine::class);

        // --- Dispatch Order A: within-tolerance, auto-approved, exited ---
        $orderA = DispatchOrder::create([
            'company_id' => $companyId, 'customer_id' => $client->id, 'transport_company_id' => $transportCo->id,
            'vehicle_registration' => 'KDQ 100A', 'trailer_number' => 'TRL-001', 'driver_name' => 'Samuel Otieno',
            'driver_phone' => '+254712000401', 'weighbridge_id' => $weighbridge1->id, 'loading_bay_id' => $bay1->id,
            'loader_equipment_id' => $loaderEquipment?->id, 'loader_operator_id' => $staff?->id,
            'material_id' => $material->id, 'stockpile_id' => $pileA->id, 'target_net_weight' => 28,
            'tolerance_tonnes' => 0.20, 'priority' => 'Normal', 'status' => 'Checked In',
            'gate_in_at' => now()->subHours(3), 'gate' => 'Gate 1', 'vehicle_inspection_passed' => true,
        ]);
        $orderA->recordTare(['tare_weight' => 14, 'tare_operator_id' => $staff?->id]);
        $liA = $orderA->currentLoadingInstruction();
        $liA->accept();
        $liA->startLoading();
        $liA->complete(28);
        $orderA->recordGross(['gross_weight' => 42.1, 'gross_operator_id' => $staff?->id]);
        $orderA->runDecisionEngine();
        $orderA->recordExit(['exit_gate' => 'Gate 1', 'security_clearance_by_id' => $staff?->id]);
        ProofOfDelivery::create([
            'company_id' => $companyId, 'dispatch_order_id' => $orderA->id, 'delivered_quantity' => $orderA->net_weight,
            'delivery_time' => now(), 'receiver_name' => 'Site Foreman — Nairobi Builders', 'status' => 'Delivered',
        ]);

        // --- Dispatch Order B: overweight -> real WorkflowEngine escalation -> approved -> exited ---
        $orderB = DispatchOrder::create([
            'company_id' => $companyId, 'customer_id' => $client->id, 'transport_company_id' => $transportCo->id,
            'vehicle_registration' => 'KDQ 200B', 'trailer_number' => 'TRL-002', 'driver_name' => 'Michael Ndung\'u',
            'driver_phone' => '+254712000402', 'weighbridge_id' => $weighbridge1->id, 'loading_bay_id' => $bay1->id,
            'loader_equipment_id' => $loaderEquipment?->id, 'loader_operator_id' => $staff?->id,
            'material_id' => $material->id, 'stockpile_id' => $pileA->id, 'target_net_weight' => 28,
            'tolerance_tonnes' => 0.20, 'priority' => 'High', 'status' => 'Checked In',
            'gate_in_at' => now()->subHours(2), 'gate' => 'Gate 1', 'vehicle_inspection_passed' => true,
        ]);
        $orderB->recordTare(['tare_weight' => 14, 'tare_operator_id' => $staff?->id]);
        $liB = $orderB->currentLoadingInstruction();
        $liB->accept();
        $liB->startLoading();
        $liB->complete(29);
        $orderB->recordGross(['gross_weight' => 43.5, 'gross_operator_id' => $staff?->id]); // net 29.5, variance 1.5 -> Critical
        $orderB->runDecisionEngine();

        $supervisorUser = User::withoutGlobalScope('company')->where('email', 'weighbridge.supervisor@riftvalleyquarries.example')->first();
        $request = $orderB->currentApproval();
        if ($request) {
            $step = $request->steps()->where('status', 'Pending')->first();
            $action = $step?->actions()->where('approver_id', $supervisorUser->id)->whereNull('action')->first();

            if ($action) {
                $engine->approve($action, 'Variance reviewed and accepted — approved for dispatch.');
            }
        }
        $orderB->refresh();
        $orderB->recordExit(['exit_gate' => 'Gate 1', 'security_clearance_by_id' => $staff?->id]);
        ProofOfDelivery::create([
            'company_id' => $companyId, 'dispatch_order_id' => $orderB->id, 'delivered_quantity' => $orderB->net_weight,
            'delivery_time' => now(), 'receiver_name' => 'Site Foreman — Nairobi Builders', 'status' => 'Delivered',
        ]);

        // --- Dispatch Order C: reload demonstration (2 gross weighings) + split load ---
        $orderC = DispatchOrder::create([
            'company_id' => $companyId, 'customer_id' => $client->id, 'transport_company_id' => $transportCo->id,
            'vehicle_registration' => 'KDQ 300C', 'trailer_number' => 'TRL-003', 'driver_name' => 'Peter Kamau',
            'driver_phone' => '+254712000403', 'weighbridge_id' => $weighbridge1->id, 'loading_bay_id' => $bay1->id,
            'loader_equipment_id' => $loaderEquipment?->id, 'loader_operator_id' => $staff?->id,
            'material_id' => $material->id, 'stockpile_id' => $pileA->id, 'target_net_weight' => 30,
            'tolerance_tonnes' => 0.20, 'priority' => 'Normal', 'status' => 'Checked In',
            'gate_in_at' => now()->subHour(), 'gate' => 'Gate 1', 'vehicle_inspection_passed' => true,
        ]);
        $orderC->recordTare(['tare_weight' => 15, 'tare_operator_id' => $staff?->id]);
        $liC = $orderC->currentLoadingInstruction();
        $liC->accept();
        $liC->startLoading();
        $liC->complete(30);
        // First gross weighing: underweight, well outside tolerance
        $orderC->recordGross(['gross_weight' => 43.5, 'gross_operator_id' => $staff?->id]); // net 28.5, variance -1.5 -> Underweight
        $orderC->runDecisionEngine();
        $orderC->refresh();
        // Truck went back to the loader for a top-up; loading instruction regenerated automatically
        $liC2 = $orderC->currentLoadingInstruction();
        $liC2->accept();
        $liC2->startLoading();
        $liC2->complete(2);
        // Second (reload) gross weighing: within tolerance
        $orderC->recordGross(['gross_weight' => 45.05, 'gross_operator_id' => $staff?->id, 'reason' => 'Top-up after underweight']);
        $orderC->runDecisionEngine();
        $orderC->refresh();
        $orderC->recordExit(['exit_gate' => 'Gate 1', 'security_clearance_by_id' => $staff?->id]);

        // Split-load demonstration on a 4th, separate order (two products on one truck)
        $orderD = DispatchOrder::create([
            'company_id' => $companyId, 'customer_id' => $client->id, 'transport_company_id' => $transportCo->id,
            'vehicle_registration' => 'KDQ 400D', 'driver_name' => 'Josephat Mwenda', 'weighbridge_id' => $weighbridge1->id,
            'material_id' => $material->id, 'stockpile_id' => $pileA->id, 'target_net_weight' => 30,
            'tare_weight' => 15, 'gross_weight' => 45, 'net_weight' => 30, 'status' => 'Gross Weighed',
            'vehicle_inspection_passed' => true,
        ]);
        DispatchOrderLine::create([
            'company_id' => $companyId, 'dispatch_order_id' => $orderD->id, 'material_id' => $material->id,
            'quantity' => 20, 'stockpile_id' => $pileA->id, 'unit_price' => $material->selling_price ?? 1800,
        ]);
        DispatchOrderLine::create([
            'company_id' => $companyId, 'dispatch_order_id' => $orderD->id, 'material_id' => $materialB->id,
            'quantity' => 10, 'stockpile_id' => $pileB->id, 'unit_price' => $materialB->selling_price ?? 1000,
        ]);
        $orderD->approveDispatch();

        // A Weighbridge Exception taken through approval
        $exception = WeighbridgeException::create([
            'company_id' => $companyId, 'dispatch_order_id' => $orderB->id, 'exception_type' => 'Overloaded',
            'reason' => 'Gross weight exceeded the allowed maximum by more than the critical variance threshold.',
            'corrective_action' => 'Supervisor reviewed and approved dispatch after confirming safe axle distribution.',
            'preventive_action' => 'Loader operator briefed on target quantities for this product.',
            'reported_by_id' => $staff?->id, 'status' => 'Draft',
        ]);
        $exception->update(['status' => 'Submitted']);
        $excRequest = $exception->submitForApproval('weighbridge-exception-approval', [
            'title' => 'Weighbridge Exception — ' . $exception->exception_number,
            'metadata' => ['weighbridge_exception_id' => $exception->id],
        ]);
        $excStep = $excRequest->steps()->where('status', 'Pending')->first();
        $excAction = $excStep?->actions()->where('approver_id', $supervisorUser->id)->whereNull('action')->first();
        if ($excAction) {
            $engine->approve($excAction, 'Confirmed safe — approved.');
        }

        $this->command?->info('SmartWeighbridgeDemoSeeder: 2 weighbridges, 2 loading bays, a transport company, 3 full-lifecycle dispatch orders (auto-approved / escalated / reload), a split-load order, an approved exception and proof-of-delivery records seeded.');
    }
}
