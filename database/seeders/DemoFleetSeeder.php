<?php

namespace Database\Seeders;

use App\Enums\TripStatus;
use App\Enums\TruckStatus;
use App\Models\Cargo;
use App\Models\Client;
use App\Models\Company;
use App\Models\Driver;
use App\Models\DriverCompanyChatMessage;
use App\Models\DriverEmergencyAlert;
use App\Models\DriverFuelReceipt;
use App\Models\DriverPod;
use App\Models\DriverPreTripInspection;
use App\Models\Employee;
use App\Models\FinancialPeriod;
use App\Models\FuelLog;
use App\Models\GoodsReceivedNote;
use App\Models\Maintenance;
use App\Models\Material;
use App\Models\MaterialDelivery;
use App\Models\Part;
use App\Models\PartUsage;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Plan;
use App\Models\ProfitCentre;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Settlement;
use App\Models\Supplier;
use App\Models\Trip;
use App\Models\Truck;
use App\Models\Tyre;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Warehouse;
use App\Services\FinancialService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One-off demo/showcase seeder — builds a fully self-contained, dedicated
 * demo company with realistic interconnected data across every Fleet
 * module, so the flow between them (trip -> invoice -> commission ->
 * wallet, maintenance -> expense, settlement -> wallet, etc.) is visible
 * without touching any real company's data. Safe to re-run: wipes and
 * rebuilds its own company by name before seeding.
 */
class DemoFleetSeeder extends Seeder
{
    private const COMPANY_NAME = 'Tanaki Demo Fleet Ltd';
    private const PASSWORD = 'Demo@2026!';

    public function run(): void
    {
        $this->wipeExisting();

        $plan = Plan::create([
            'name' => 'Demo Enterprise',
            'price' => 24999,
            'truck_limit' => 50,
            'driver_limit' => 50,
            'ai_requests_limit' => 1000,
            'is_featured' => false,
            'is_default' => false,
        ]);

        $company = Company::create([
            'name' => self::COMPANY_NAME,
            'email' => 'demo@tanakihub.test',
            'status' => 'active',
            'industry' => 'trucking',
            'plan_id' => $plan->id,
            'trial_ends_at' => now()->addYears(2),
            'is_active' => true,
            'country' => 'KE',
        ]);

        // Company::booted() already auto-creates all 3 wallet types at
        // balance=0 on creation — fund them so trip completions / commission
        // postings succeed instead of hitting the "insufficient funds" guard
        // on a brand-new, never-funded company.
        Wallet::where('company_id', $company->id)->update([
            'available_balance' => 100_000_00,
            'ledger_balance' => 100_000_00,
        ]);

        $admin = User::create([
            'company_id' => $company->id,
            'name' => 'Demo Company Admin',
            'email' => 'demo.admin@tanakihub.test',
            'password' => Hash::make(self::PASSWORD),
        ]);
        $admin->assignRole('Company Admin');

        $fleetManager = User::create([
            'company_id' => $company->id,
            'name' => 'Demo Fleet Manager',
            'email' => 'demo.fleetmanager@tanakihub.test',
            'password' => Hash::make(self::PASSWORD),
        ]);
        $fleetManager->assignRole('Fleet Manager');

        // --- Trucks ---
        $truck1 = Truck::create(['company_id' => $company->id, 'plate_number' => 'KDA 001A', 'truck_type' => 'Flatbed', 'axle_config' => '6x4', 'tyre_count' => 6, 'brand' => 'Isuzu', 'model' => 'FRR', 'year' => 2021, 'fuel_type' => 'Diesel', 'current_mileage' => 84500, 'capacity_tons' => 10, 'status' => TruckStatus::Available]);
        $truck2 = Truck::create(['company_id' => $company->id, 'plate_number' => 'KDB 002B', 'truck_type' => 'Box Body', 'axle_config' => '6x4', 'tyre_count' => 6, 'brand' => 'Mitsubishi', 'model' => 'Fuso', 'year' => 2020, 'fuel_type' => 'Diesel', 'current_mileage' => 132000, 'capacity_tons' => 8, 'status' => TruckStatus::InTransit]);
        $truck3 = Truck::create(['company_id' => $company->id, 'plate_number' => 'KDC 003C', 'truck_type' => 'Tanker', 'axle_config' => '8x4', 'tyre_count' => 10, 'brand' => 'Scania', 'model' => 'P360', 'year' => 2019, 'fuel_type' => 'Diesel', 'current_mileage' => 210300, 'capacity_tons' => 20, 'status' => TruckStatus::UnderMaintenance]);
        $truck4 = Truck::create(['company_id' => $company->id, 'plate_number' => 'KDD 004D', 'truck_type' => 'Flatbed', 'axle_config' => '6x4', 'tyre_count' => 6, 'brand' => 'Isuzu', 'model' => 'NQR', 'year' => 2022, 'fuel_type' => 'Diesel', 'current_mileage' => 41200, 'capacity_tons' => 7, 'status' => TruckStatus::Available]);

        // --- Employees (HR records) ---
        $johnEmp = Employee::create(['company_id' => $company->id, 'full_name' => 'John Mwangi', 'email' => 'john.mwangi@tanakihub.test', 'phone_number' => '0711000001', 'national_id' => 'DEMO0001', 'license_number' => 'DL-0001', 'license_expiry' => now()->addYears(2), 'category' => 'Driver', 'status' => 'active', 'date_hired' => now()->subYear(), 'emergency_contact_name' => 'Ann Mwangi', 'emergency_contact_phone' => '0722000001', 'base_salary' => 45000, 'allowances' => 0, 'tax_deduction' => 0]);
        $maryEmp = Employee::create(['company_id' => $company->id, 'full_name' => 'Mary Wanjiru', 'email' => 'mary.wanjiru@tanakihub.test', 'phone_number' => '0711000002', 'national_id' => 'DEMO0002', 'license_number' => 'DL-0002', 'license_expiry' => now()->addYears(2), 'category' => 'Driver', 'status' => 'active', 'date_hired' => now()->subMonths(8), 'emergency_contact_name' => 'Sam Wanjiru', 'emergency_contact_phone' => '0722000002', 'base_salary' => 42000, 'allowances' => 0, 'tax_deduction' => 0]);
        $peterEmp = Employee::create(['company_id' => $company->id, 'full_name' => 'Peter Otieno', 'email' => 'peter.otieno@tanakihub.test', 'phone_number' => '0711000003', 'national_id' => 'DEMO0003', 'license_number' => 'DL-0003', 'license_expiry' => now()->addYear(), 'category' => 'Driver', 'status' => 'active', 'date_hired' => now()->subMonths(5), 'emergency_contact_name' => 'Grace Otieno', 'emergency_contact_phone' => '0722000003', 'base_salary' => 42000, 'allowances' => 0, 'tax_deduction' => 0]);
        Employee::create(['company_id' => $company->id, 'full_name' => 'James Kariuki', 'email' => 'james.kariuki@tanakihub.test', 'phone_number' => '0711000004', 'national_id' => 'DEMO0004', 'license_number' => 'DL-0004', 'license_expiry' => now()->addYear(), 'category' => 'Driver', 'status' => 'active', 'date_hired' => now()->subMonths(3), 'emergency_contact_name' => 'Ruth Kariuki', 'emergency_contact_phone' => '0722000004', 'base_salary' => 40000, 'allowances' => 0, 'tax_deduction' => 0]);
        Employee::create(['company_id' => $company->id, 'full_name' => 'Grace Achieng', 'email' => 'grace.achieng@tanakihub.test', 'phone_number' => '0711000005', 'national_id' => 'DEMO0005', 'license_number' => 'N/A', 'license_expiry' => now()->addYear(), 'category' => 'Workshop', 'status' => 'active', 'date_hired' => now()->subYears(2), 'emergency_contact_name' => 'Paul Achieng', 'emergency_contact_phone' => '0722000005', 'base_salary' => 38000, 'allowances' => 0, 'tax_deduction' => 0]);

        // --- Driver mobile-app accounts (real FK-linked, not email-matched) ---
        $johnUser = User::create(['company_id' => $company->id, 'name' => 'John Mwangi', 'email' => 'driver.john@tanakihub.test', 'password' => Hash::make(self::PASSWORD)]);
        $johnUser->assignRole('Driver');
        $johnDriver = Driver::where('user_id', $johnUser->id)->first();
        $johnDriver->update(['employee_id' => $johnEmp->id, 'commission_rate' => 10, 'license_number' => $johnEmp->license_number]);

        $maryUser = User::create(['company_id' => $company->id, 'name' => 'Mary Wanjiru', 'email' => 'driver.mary@tanakihub.test', 'password' => Hash::make(self::PASSWORD)]);
        $maryUser->assignRole('Driver');
        $maryDriver = Driver::where('user_id', $maryUser->id)->first();
        $maryDriver->update(['employee_id' => $maryEmp->id, 'commission_rate' => 12, 'license_number' => $maryEmp->license_number]);

        $peterUser = User::create(['company_id' => $company->id, 'name' => 'Peter Otieno', 'email' => 'driver.peter@tanakihub.test', 'password' => Hash::make(self::PASSWORD)]);
        $peterUser->assignRole('Driver');
        $peterDriver = Driver::where('user_id', $peterUser->id)->first();
        $peterDriver->update(['employee_id' => $peterEmp->id, 'commission_rate' => 10, 'license_number' => $peterEmp->license_number]);

        // --- Clients ---
        $bidco = Client::create(['company_id' => $company->id, 'company_name' => 'Bidco Africa Ltd', 'contact_person' => 'Alice Njeri', 'phone' => '0733000001', 'email' => 'alice@bidco-demo.test']);
        $kbl = Client::create(['company_id' => $company->id, 'company_name' => 'Kenya Breweries Ltd', 'contact_person' => 'Brian Kimani', 'phone' => '0733000002', 'email' => 'brian@kbl-demo.test']);
        $naivas = Client::create(['company_id' => $company->id, 'company_name' => 'Naivas Supermarket', 'contact_person' => 'Cynthia Mutua', 'phone' => '0733000003', 'email' => 'cynthia@naivas-demo.test']);

        // --- Cargo + Trips ---
        $cargo1 = Cargo::create(['company_id' => $company->id, 'client_id' => $bidco->id, 'description' => 'Cooking oil pallets', 'cargo_type' => 'Consumer Goods', 'weight' => 8000]);
        $cargo2 = Cargo::create(['company_id' => $company->id, 'client_id' => $kbl->id, 'description' => 'Beverage crates', 'cargo_type' => 'Beverages', 'weight' => 6000]);
        $cargo3 = Cargo::create(['company_id' => $company->id, 'client_id' => $naivas->id, 'description' => 'Retail dry goods', 'cargo_type' => 'General Goods', 'weight' => 4000]);
        $cargo4 = Cargo::create(['company_id' => $company->id, 'client_id' => $bidco->id, 'description' => 'Return pallets', 'cargo_type' => 'Consumer Goods', 'weight' => 3000]);

        // Trip 1 — completed: John, truck1, Bidco. Triggers invoice + commission + wallet credit.
        $trip1 = Trip::create(['company_id' => $company->id, 'truck_id' => $truck1->id, 'employee_id' => $johnEmp->id, 'client_id' => $bidco->id, 'cargo_id' => $cargo1->id, 'origin' => 'Nairobi', 'destination' => 'Mombasa', 'cargo_type' => 'Consumer Goods', 'cargo_weight' => 8000, 'revenue' => 80000, 'status' => TripStatus::InProgress, 'departure_time' => now()->subDays(3), 'expected_arrival' => now()->subDays(2)]);
        $trip1->update(['status' => TripStatus::Completed, 'actual_arrival' => now()->subDays(2)]);

        // Trip 2 — completed: Mary, truck2 route, KBL. Triggers invoice + commission + wallet credit.
        $trip2 = Trip::create(['company_id' => $company->id, 'truck_id' => $truck4->id, 'employee_id' => $maryEmp->id, 'client_id' => $kbl->id, 'cargo_id' => $cargo2->id, 'origin' => 'Nairobi', 'destination' => 'Kisumu', 'cargo_type' => 'Beverages', 'cargo_weight' => 6000, 'revenue' => 45000, 'status' => TripStatus::InProgress, 'departure_time' => now()->subDay(), 'expected_arrival' => now()->subHours(6)]);
        $trip2->update(['status' => TripStatus::Completed, 'actual_arrival' => now()->subHours(5)]);

        // Trip 3 — currently in progress: Peter, truck2 (In Transit), Naivas.
        $trip3 = Trip::create(['company_id' => $company->id, 'truck_id' => $truck2->id, 'employee_id' => $peterEmp->id, 'client_id' => $naivas->id, 'cargo_id' => $cargo3->id, 'origin' => 'Nairobi', 'destination' => 'Nakuru', 'cargo_type' => 'General Goods', 'cargo_weight' => 4000, 'revenue' => 30000, 'status' => TripStatus::InProgress, 'departure_time' => now()->subHours(3), 'expected_arrival' => now()->addHours(2)]);

        // Trip 4 — scheduled, not yet dispatched: John, truck4, Bidco return leg.
        Trip::create(['company_id' => $company->id, 'truck_id' => $truck4->id, 'employee_id' => $johnEmp->id, 'client_id' => $bidco->id, 'cargo_id' => $cargo4->id, 'origin' => 'Mombasa', 'destination' => 'Nairobi', 'cargo_type' => 'Consumer Goods', 'cargo_weight' => 3000, 'revenue' => 60000, 'status' => TripStatus::Scheduled, 'departure_time' => now()->addDay(), 'expected_arrival' => now()->addDays(2)]);

        // --- Payment against trip1's auto-generated invoice ---
        $invoice1 = $trip1->invoices()->first() ?? \App\Models\Invoice::where('trip_id', $trip1->id)->first();
        if ($invoice1) {
            Payment::create([
                'company_id' => $company->id,
                'client_id' => $bidco->id,
                'trip_id' => $trip1->id,
                'invoice_id' => $invoice1->id,
                'amount' => $invoice1->total_amount,
                'payment_method' => 'Bank Transfer',
                'status' => 'Paid',
                'invoice_number' => $invoice1->invoice_number,
                'payment_date' => now()->subDay(),
            ]);
        }

        // --- Maintenance: one completed (generates Expense), one pending ---
        Maintenance::create(['company_id' => $company->id, 'truck_id' => $truck3->id, 'maintenance_type' => 'Engine Overhaul', 'description' => 'Full engine service and gasket replacement', 'service_provider' => 'AutoCare Workshop', 'cost' => 25000, 'maintenance_date' => now()->subDays(2), 'status' => 'Completed']);
        Maintenance::create(['company_id' => $company->id, 'truck_id' => $truck1->id, 'maintenance_type' => 'Brake Inspection', 'description' => 'Scheduled 90-day brake check', 'maintenance_date' => now()->addDays(5), 'status' => 'Pending']);

        // --- Fuel logs (each auto-generates an Expense) ---
        FuelLog::create(['company_id' => $company->id, 'truck_id' => $truck1->id, 'employee_id' => $johnEmp->id, 'fuel_station' => 'Shell Mombasa Road', 'quantity_litres' => 150, 'cost_per_litre' => 195, 'total_cost' => 29250, 'mileage_at_fueling' => 84500, 'date' => now()->subDays(3)]);
        FuelLog::create(['company_id' => $company->id, 'truck_id' => $truck4->id, 'employee_id' => $maryEmp->id, 'fuel_station' => 'TotalEnergies Uhuru Hwy', 'quantity_litres' => 120, 'cost_per_litre' => 193, 'total_cost' => 23160, 'mileage_at_fueling' => 41200, 'date' => now()->subDay()]);

        // --- Settlement: paid, credits John's wallet directly (separate from his trip commission) ---
        Settlement::create(['settlement_id' => 'STL-DEMO-0001', 'company_id' => $company->id, 'truck_id' => $truck1->id, 'employee_id' => $johnEmp->id, 'settlement_date' => now()->subDays(1), 'pay_period_start' => now()->subDays(14), 'pay_period_end' => now(), 'net_amount' => 15000, 'status' => 'PAID']);

        // --- Parts + usage against the completed maintenance ---
        $brakePads = Part::create(['company_id' => $company->id, 'name' => 'Brake Pads (Heavy Duty)', 'part_number' => 'BP-HD-001', 'category' => 'Brakes', 'unit_price' => 3500, 'stock_quantity' => 20, 'reorder_level' => 5]);
        Part::create(['company_id' => $company->id, 'name' => 'Oil Filter', 'part_number' => 'OF-STD-002', 'category' => 'Engine', 'unit_price' => 850, 'stock_quantity' => 40, 'reorder_level' => 10]);
        $maintenance1 = Maintenance::where('truck_id', $truck3->id)->where('status', 'Completed')->first();
        PartUsage::create(['company_id' => $company->id, 'maintenance_id' => $maintenance1->id, 'part_id' => $brakePads->id, 'quantity' => 4, 'unit_price' => 3500, 'total_price' => 14000]);

        // --- Tyres: 4 installed on truck1, 2 spare ---
        $positions = ['Front-Left', 'Front-Right', 'Rear-Left', 'Rear-Right'];
        foreach ($positions as $i => $position) {
            Tyre::create(['company_id' => $company->id, 'truck_id' => $truck1->id, 'serial_number' => 'TY-DEMO-' . ($i + 1), 'brand' => 'Michelin', 'size' => '295/80R22.5', 'type' => 'Radial', 'position' => $position, 'status' => 'Active', 'installation_date' => now()->subMonths(2), 'tread_depth' => 8.5]);
        }
        Tyre::create(['company_id' => $company->id, 'serial_number' => 'TY-DEMO-5', 'brand' => 'Michelin', 'size' => '295/80R22.5', 'type' => 'Radial', 'status' => 'Spare', 'tread_depth' => 10]);
        Tyre::create(['company_id' => $company->id, 'serial_number' => 'TY-DEMO-6', 'brand' => 'Bridgestone', 'size' => '295/80R22.5', 'type' => 'Radial', 'status' => 'Spare', 'tread_depth' => 10]);

        // --- Driver Submissions: SOS, chat, fuel receipt, pre-trip inspection, POD ---
        DriverEmergencyAlert::create(['driver_id' => $peterDriver->id, 'trip_id' => $trip3->id, 'type' => 'sos', 'status' => 'open', 'message' => 'Flat tyre on the highway, need roadside assistance.', 'latitude' => -0.9, 'longitude' => 36.4, 'triggered_at' => now()->subMinutes(20)]);

        DriverCompanyChatMessage::create(['company_id' => $company->id, 'driver_id' => $maryDriver->id, 'sender_type' => 'driver', 'message' => 'Delivery at KBL depot done, heading back.']);
        DriverCompanyChatMessage::create(['company_id' => $company->id, 'driver_id' => $maryDriver->id, 'sender_type' => 'company', 'sender_user_id' => $fleetManager->id, 'message' => 'Great work, drive safe.']);

        DriverFuelReceipt::create(['driver_id' => $johnDriver->id, 'trip_id' => $trip1->id, 'vendor' => 'Shell Mombasa Road', 'litres' => 150, 'amount' => 29250, 'receipt_path' => 'demo/receipts/placeholder.jpg', 'status' => 'pending', 'captured_at' => now()->subDays(3)]);

        DriverPreTripInspection::create(['driver_id' => $peterDriver->id, 'trip_id' => $trip3->id, 'brakes' => true, 'lights' => true, 'tires' => true, 'load_secured' => true, 'notes' => 'All checks passed.', 'submitted_at' => now()->subHours(3)]);

        DriverPod::create(['company_id' => $company->id, 'driver_id' => $johnDriver->id, 'trip_id' => $trip1->id, 'image_path' => 'demo/pods/placeholder.jpg', 'status' => 'approved', 'receiver_name' => 'Alice Njeri', 'delivered_at' => now()->subDays(2)]);

        // --- Accounting: Financial Period + Profit Centre ---
        // Chart of Accounts / Journal Entries are NOT seeded here — the 19
        // system accounts are global (company_id NULL, shared across every
        // company) and every Expense/Invoice/Payment/Settlement above already
        // posted a real JournalEntry against them via PostingEngineService.
        FinancialPeriod::create(['company_id' => $company->id, 'name' => now()->format('F Y'), 'period_start' => now()->startOfMonth(), 'period_end' => now()->endOfMonth(), 'status' => 'Open']);
        FinancialPeriod::create(['company_id' => $company->id, 'name' => now()->subMonth()->format('F Y'), 'period_start' => now()->subMonth()->startOfMonth(), 'period_end' => now()->subMonth()->endOfMonth(), 'status' => 'Closed']);
        ProfitCentre::create(['company_id' => $company->id, 'code' => 'PC-MSA', 'name' => 'Mombasa Route', 'dimension_type' => 'Region', 'dimension_label' => 'Nairobi–Mombasa corridor', 'status' => 'Active']);
        ProfitCentre::create(['company_id' => $company->id, 'code' => 'PC-KSM', 'name' => 'Kisumu Route', 'dimension_type' => 'Region', 'dimension_label' => 'Nairobi–Kisumu corridor', 'status' => 'Active']);

        // --- Procurement: Supplier -> Purchase Request -> Purchase Order -> Warehouse/Material -> Delivery -> GRN ---
        // GRN::approve() is the one real integration point this module has —
        // it actually credits StockLevel + Material.quantity_on_hand and
        // writes an InventoryMovement + PutAwayTask. PO approval itself has
        // no financial side effects in the current codebase (a confirmed gap,
        // not something this seeder fakes), so the two POs below are left as
        // plain records showing the pipeline, not linked to any Expense.
        $supplier = Supplier::create(['company_id' => $company->id, 'company_name' => 'AutoParts Kenya Ltd', 'contact_person' => 'Daniel Kimutai', 'phone' => '0700500001', 'email' => 'sales@autoparts-demo.test', 'status' => 'Active', 'risk_rating' => 'Low']);

        $warehouse = Warehouse::create(['company_id' => $company->id, 'name' => 'Nairobi Main Warehouse', 'code' => 'WH-NBI-01', 'type' => 'Main', 'location' => 'Industrial Area, Nairobi', 'status' => 'Active']);

        $material = Material::create(['company_id' => $company->id, 'name' => 'Truck Tyres (295/80R22.5)', 'material_code' => 'MAT-TYRE-001', 'unit' => 'pcs', 'unit_cost' => 28000, 'reorder_level' => 4, 'quantity_on_hand' => 0, 'status' => 'Active']);

        $purchaseRequest = PurchaseRequest::create(['company_id' => $company->id, 'material_id' => $material->id, 'quantity' => 8, 'priority' => 'High', 'status' => 'Approved', 'source' => 'Manual', 'supplier_id' => $supplier->id, 'currency' => 'KES', 'estimated_cost' => 8 * 28000, 'reason' => 'Replacement stock for scheduled tyre rotation']);

        $purchaseOrder = PurchaseOrder::create(['company_id' => $company->id, 'supplier_id' => $supplier->id, 'purchase_request_id' => $purchaseRequest->id, 'currency' => 'KES', 'subtotal' => 224000, 'tax_amount' => 35840, 'discount_amount' => 0, 'total_amount' => 259840, 'status' => 'Approved', 'expected_delivery' => now()->addDays(5)]);

        // Second PO left pending approval, so the pipeline shows more than one stage.
        PurchaseOrder::create(['company_id' => $company->id, 'supplier_id' => $supplier->id, 'currency' => 'KES', 'subtotal' => 42000, 'tax_amount' => 6720, 'discount_amount' => 0, 'total_amount' => 48720, 'status' => 'Pending', 'expected_delivery' => now()->addDays(10)]);

        $delivery = MaterialDelivery::create(['company_id' => $company->id, 'material_id' => $material->id, 'warehouse_id' => $warehouse->id, 'purchase_order_id' => $purchaseOrder->id, 'supplier_id' => $supplier->id, 'supplier_name' => $supplier->company_name, 'quantity' => 8, 'delivered_at' => now()->subDay(), 'status' => 'Delivered', 'inspection_status' => 'Passed']);

        $grn = GoodsReceivedNote::create(['company_id' => $company->id, 'material_delivery_id' => $delivery->id, 'received_quantity' => 8, 'accepted_quantity' => 7, 'rejected_quantity' => 0, 'damaged_quantity' => 1, 'quality_approved' => true, 'status' => 'Pending', 'inspection_notes' => 'One tyre had sidewall damage from transit — rejected.']);
        $grn->approve(); // the real flow: credits StockLevel + Material.quantity_on_hand, writes InventoryMovement + PutAwayTask

        // --- Payouts: pending driver payout, left un-approved deliberately ---
        // (approving it calls the real Paystack transfer API — left for you
        // to trigger yourself from the panel with your own gateway keys.)
        Payout::create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'recipient_id' => (string) $maryDriver->id,
            'recipient_type' => 'driver',
            'amount' => 300000, // KES 3,000 in cents — within Mary's KES 5,400 wallet balance
            'currency' => 'KES',
            'bank_code' => 'MPESA',
            'account_number' => '254722000002',
            'status' => 'pending',
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $this->command?->info('Demo Fleet company seeded: ' . self::COMPANY_NAME);
        $this->command?->info('Login: demo.admin@tanakihub.test / ' . self::PASSWORD);
        $this->command?->info('Login: demo.fleetmanager@tanakihub.test / ' . self::PASSWORD);
    }

    private function wipeExisting(): void
    {
        $company = Company::where('name', self::COMPANY_NAME)->first();
        if (! $company) {
            return;
        }

        $companyId = $company->id;
        $driverIds = Driver::where('company_id', $companyId)->pluck('id');

        \App\Models\DriverWalletTransaction::whereIn('driver_id', $driverIds)->delete();
        \App\Models\DriverWallet::whereIn('driver_id', $driverIds)->delete();
        DriverEmergencyAlert::whereIn('driver_id', $driverIds)->delete();
        DriverCompanyChatMessage::where('company_id', $companyId)->delete();
        DriverFuelReceipt::whereIn('driver_id', $driverIds)->delete();
        DriverPreTripInspection::whereIn('driver_id', $driverIds)->delete();
        DriverPod::where('company_id', $companyId)->delete();

        Payout::where('company_id', $companyId)->delete();
        \App\Models\PutAwayTask::where('company_id', $companyId)->delete();
        \App\Models\InventoryMovement::where('company_id', $companyId)->delete();
        \App\Models\StockLevel::where('company_id', $companyId)->delete();
        GoodsReceivedNote::where('company_id', $companyId)->delete();
        MaterialDelivery::where('company_id', $companyId)->delete();
        PurchaseOrder::where('company_id', $companyId)->delete();
        PurchaseRequest::where('company_id', $companyId)->delete();
        Material::where('company_id', $companyId)->delete();
        Warehouse::where('company_id', $companyId)->delete();
        Supplier::where('company_id', $companyId)->delete();
        ProfitCentre::where('company_id', $companyId)->delete();
        FinancialPeriod::where('company_id', $companyId)->delete();
        \App\Models\JournalLine::whereHas('journalEntry', fn ($q) => $q->where('company_id', $companyId))->delete();
        \App\Models\JournalEntry::where('company_id', $companyId)->delete();

        \App\Models\LedgerEntry::where('company_id', $companyId)->delete();
        Payment::where('company_id', $companyId)->delete();
        \App\Models\Invoice::where('company_id', $companyId)->delete();
        \App\Models\Expense::where('company_id', $companyId)->delete();
        PartUsage::where('company_id', $companyId)->delete();
        Part::where('company_id', $companyId)->delete();
        Settlement::where('company_id', $companyId)->delete();
        FuelLog::where('company_id', $companyId)->delete();
        Maintenance::where('company_id', $companyId)->delete();
        Tyre::where('company_id', $companyId)->delete();
        Trip::where('company_id', $companyId)->delete();
        Cargo::where('company_id', $companyId)->delete();
        Client::where('company_id', $companyId)->delete();
        Driver::where('company_id', $companyId)->delete();
        Employee::where('company_id', $companyId)->delete();
        Truck::where('company_id', $companyId)->delete();
        Wallet::where('company_id', $companyId)->delete();

        $userIds = User::where('company_id', $companyId)->pluck('id');
        foreach ($userIds as $userId) {
            User::find($userId)?->roles()->detach();
        }
        User::whereIn('id', $userIds)->delete();

        $oldPlanId = $company->plan_id;
        $company->delete();
        if ($oldPlanId) {
            Plan::where('id', $oldPlanId)->delete();
        }
    }
}
