<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Company;
use App\Models\CrushingPlant;
use App\Models\CrushingPlantLog;
use App\Models\Employee;
use App\Models\Equipment;
use App\Models\ExtractionSite;
use App\Models\FuelLog;
use App\Models\FuelStock;
use App\Models\Maintenance;
use App\Models\Material;
use App\Models\Payment;
use App\Models\ProductionLog;
use App\Models\SafetyIncident;
use App\Models\Staff;
use App\Models\Stockpile;
use App\Models\User;
use App\Models\WeighbridgeTicket;
use Illuminate\Database\Seeder;

/**
 * Real demo data proving the Quarry Dashboard module end-to-end: a genuine
 * Quarry company with 2 extraction sites, crushing plants, stockpiles
 * (including a deliberately low one), weighbridge tickets, fuel stock,
 * equipment/maintenance and safety incidents — so every widget on all 7
 * dashboards renders real figures instead of empty states. Idempotent.
 */
class QuarryDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Company::withoutGlobalScope('company')->where('email', 'info@riftvalleyquarries.example')->exists()) {
            $this->command?->info('QuarryDemoSeeder: demo data already exists, skipping.');

            return;
        }

        $company = Company::create([
            'name' => 'Rift Valley Quarries Ltd',
            'email' => 'info@riftvalleyquarries.example',
            'phone' => '+254712000100',
            'industry' => 'quarry',
            'status' => 'Active',
            'trial_ends_at' => now()->addDays(90),
        ]);
        $companyId = $company->id;

        $admin = User::create([
            'company_id' => $companyId,
            'name' => 'Rift Valley Admin',
            'email' => 'quarry.admin@riftvalleyquarries.example',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Company Admin');

        $manager = User::create([
            'company_id' => $companyId,
            'name' => 'Peter Kariuki',
            'email' => 'quarry.manager@riftvalleyquarries.example',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $manager->assignRole('Quarry Manager');

        $weighbridgeOperator = User::create([
            'company_id' => $companyId,
            'name' => 'Grace Wanjiku',
            'email' => 'weighbridge.operator@riftvalleyquarries.example',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $weighbridgeOperator->assignRole('Weighbridge Operator');

        $crusherOperator = Staff::create([
            'company_id' => $companyId,
            'full_name' => 'John Mwangi',
            'phone_number' => '+254712000101',
            'email' => 'john.mwangi@riftvalleyquarries.example',
            'employment_type' => 'Permanent',
            'date_hired' => now()->subYears(2),
            'status' => 'Active',
        ]);

        $weighbridgeStaff = Staff::create([
            'company_id' => $companyId,
            'full_name' => 'Grace Wanjiku',
            'phone_number' => '+254712000102',
            'email' => 'grace.wanjiku@riftvalleyquarries.example',
            'employment_type' => 'Permanent',
            'date_hired' => now()->subYear(),
            'status' => 'Active',
        ]);

        $northSite = ExtractionSite::create([
            'company_id' => $companyId,
            'name' => 'Rift Valley Quarry — North Pit',
            'location' => 'Naivasha, Nakuru County',
            'material_type' => 'Granite',
            'status' => 'Active',
            'estimated_reserves' => 850000,
            'daily_target_tonnes' => 450,
            'gps_lat' => -0.7167,
            'gps_lng' => 36.4333,
        ]);

        $southSite = ExtractionSite::create([
            'company_id' => $companyId,
            'name' => 'Rift Valley Quarry — South Pit',
            'location' => 'Kajiado County',
            'material_type' => 'Limestone',
            'status' => 'Active',
            'estimated_reserves' => 620000,
            'daily_target_tonnes' => 300,
            'gps_lat' => -1.8500,
            'gps_lng' => 36.7833,
        ]);

        $aggregate20 = Material::create([
            'company_id' => $companyId, 'name' => 'Granite Aggregate 20mm', 'unit' => 'tonne',
            'unit_cost' => 1200, 'selling_price' => 1800, 'quantity_on_hand' => 0, 'reorder_level' => 50,
        ]);
        $aggregate40 = Material::create([
            'company_id' => $companyId, 'name' => 'Granite Aggregate 40mm', 'unit' => 'tonne',
            'unit_cost' => 1100, 'selling_price' => 1650, 'quantity_on_hand' => 0, 'reorder_level' => 50,
        ]);
        $dust = Material::create([
            'company_id' => $companyId, 'name' => 'Quarry Dust', 'unit' => 'tonne',
            'unit_cost' => 700, 'selling_price' => 1000, 'quantity_on_hand' => 0, 'reorder_level' => 30,
        ]);
        $limestoneChips = Material::create([
            'company_id' => $companyId, 'name' => 'Limestone Chips', 'unit' => 'tonne',
            'unit_cost' => 950, 'selling_price' => 1400, 'quantity_on_hand' => 0, 'reorder_level' => 40,
        ]);

        $northPlant = CrushingPlant::create([
            'company_id' => $companyId, 'extraction_site_id' => $northSite->id,
            'name' => 'North Primary Crusher', 'status' => 'Running', 'capacity_tph' => 250,
            'operator_id' => $crusherOperator->id,
        ]);
        $southPlant = CrushingPlant::create([
            'company_id' => $companyId, 'extraction_site_id' => $southSite->id,
            'name' => 'South Secondary Crusher', 'status' => 'Idle', 'capacity_tph' => 150,
            'operator_id' => $crusherOperator->id,
        ]);

        // 14 days of production + crusher shift logs, both sites
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);

            ProductionLog::create([
                'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'date' => $date,
                'material_type' => 'Granite', 'quantity_tonnes' => rand(380, 470), 'shift' => 'Day',
                'logged_by_id' => $crusherOperator->id,
            ]);
            ProductionLog::create([
                'company_id' => $companyId, 'extraction_site_id' => $southSite->id, 'date' => $date,
                'material_type' => 'Limestone', 'quantity_tonnes' => rand(220, 310), 'shift' => 'Day',
                'logged_by_id' => $crusherOperator->id,
            ]);

            CrushingPlantLog::create([
                'company_id' => $companyId, 'crushing_plant_id' => $northPlant->id, 'date' => $date, 'shift' => 'Day',
                'throughput_tonnes' => rand(400, 500), 'output_tonnes' => rand(350, 450),
                'fuel_usage_litres' => rand(180, 260), 'power_consumption_kwh' => rand(900, 1200),
                'downtime_minutes' => rand(0, 45), 'operator_id' => $crusherOperator->id,
            ]);
            CrushingPlantLog::create([
                'company_id' => $companyId, 'crushing_plant_id' => $southPlant->id, 'date' => $date, 'shift' => 'Day',
                'throughput_tonnes' => rand(150, 250), 'output_tonnes' => rand(120, 200),
                'fuel_usage_litres' => rand(90, 150), 'power_consumption_kwh' => rand(500, 700),
                'downtime_minutes' => rand(0, 60), 'operator_id' => $crusherOperator->id,
            ]);
        }

        // Stockpiles — one deliberately below its minimum to prove the low-stock alert path
        Stockpile::create([
            'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'material_id' => $aggregate20->id,
            'label' => 'Stockyard A — Bay 1', 'quantity_available' => 320, 'quantity_reserved' => 40,
            'min_level' => 100, 'max_level' => 800, 'unit_value' => 1800,
        ]);
        Stockpile::create([
            'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'material_id' => $aggregate40->id,
            'label' => 'Stockyard A — Bay 2', 'quantity_available' => 15, 'quantity_reserved' => 0,
            'min_level' => 80, 'max_level' => 600, 'unit_value' => 1650,
        ]);
        Stockpile::create([
            'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'material_id' => $dust->id,
            'label' => 'Stockyard A — Bay 3', 'quantity_available' => 210, 'quantity_reserved' => 10,
            'min_level' => 50, 'max_level' => 400, 'unit_value' => 1000,
        ]);
        Stockpile::create([
            'company_id' => $companyId, 'extraction_site_id' => $southSite->id, 'material_id' => $limestoneChips->id,
            'label' => 'Stockyard B — Bay 1', 'quantity_available' => 180, 'quantity_reserved' => 20,
            'min_level' => 60, 'max_level' => 500, 'unit_value' => 1400,
        ]);

        FuelStock::create(['company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'current_level_litres' => 4200, 'capacity_litres' => 10000]);
        FuelStock::create(['company_id' => $companyId, 'extraction_site_id' => $southSite->id, 'current_level_litres' => 900, 'capacity_litres' => 6000]);

        // Customers
        $clientA = Client::create(['company_id' => $companyId, 'company_name' => 'Nairobi Builders Ltd', 'contact_person' => 'Samuel Otieno', 'phone' => '+254722000201', 'email' => 'procurement@nairobibuilders.example', 'status' => 'Active']);
        $clientB = Client::create(['company_id' => $companyId, 'company_name' => 'Coastal Construction Supplies', 'contact_person' => 'Fatuma Ali', 'phone' => '+254722000202', 'email' => 'orders@coastalsupplies.example', 'status' => 'Active']);
        $clientC = Client::create(['company_id' => $companyId, 'company_name' => 'Great Lakes Infrastructure', 'contact_person' => 'David Mutiso', 'phone' => '+254722000203', 'email' => 'buying@greatlakesinfra.example', 'status' => 'Active']);

        // Weighbridge tickets — 14 days of outbound dispatches + a couple inbound + one delayed pending
        $customers = [$clientA, $clientB, $clientC];
        $materials = [$aggregate20, $aggregate40, $dust, $limestoneChips];

        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $ticketsToday = $i === 0 ? 4 : rand(2, 5);

            for ($t = 0; $t < $ticketsToday; $t++) {
                $gateIn = $day->copy()->setTime(rand(6, 16), rand(0, 59));
                $gateOut = $gateIn->copy()->addMinutes(rand(20, 55));
                $gross = rand(280, 340) / 10;
                $tare = rand(90, 110) / 10;

                WeighbridgeTicket::create([
                    'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'direction' => 'Outbound',
                    'vehicle_registration' => 'KD' . rand(100, 999) . 'X', 'driver_name' => 'Driver ' . rand(1, 20),
                    'material_id' => $materials[array_rand($materials)]->id, 'gross_weight' => $gross, 'tare_weight' => $tare,
                    'customer_id' => $customers[array_rand($customers)]->id, 'gate_in_at' => $gateIn, 'gate_out_at' => $gateOut,
                    'status' => 'Weighed', 'operator_id' => $weighbridgeStaff->id, 'created_at' => $gateIn, 'updated_at' => $gateOut,
                ]);
            }
        }

        // A couple of inbound (raw material delivery) tickets
        WeighbridgeTicket::create([
            'company_id' => $companyId, 'extraction_site_id' => $southSite->id, 'direction' => 'Inbound',
            'vehicle_registration' => 'KD456Y', 'driver_name' => 'Driver 21', 'gross_weight' => 32.5, 'tare_weight' => 11.2,
            'gate_in_at' => now()->subHours(3), 'gate_out_at' => now()->subHours(2)->subMinutes(40), 'status' => 'Weighed',
            'operator_id' => $weighbridgeStaff->id,
        ]);

        // A delayed pending ticket to prove the delayed-dispatch alert path
        WeighbridgeTicket::create([
            'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'direction' => 'Outbound',
            'vehicle_registration' => 'KD789Z', 'driver_name' => 'Driver 22', 'material_id' => $aggregate20->id,
            'customer_id' => $clientA->id, 'gate_in_at' => now()->subMinutes(95), 'status' => 'Pending',
            'operator_id' => $weighbridgeStaff->id,
        ]);

        // Equipment fleet + fuel + maintenance
        $loader = Equipment::create(['company_id' => $companyId, 'name' => 'Wheel Loader CAT 950', 'equipment_type' => 'Loader', 'status' => 'In Use', 'purchase_cost' => 8500000, 'current_value' => 6200000]);
        $excavator = Equipment::create(['company_id' => $companyId, 'name' => 'Excavator Komatsu PC300', 'equipment_type' => 'Excavator', 'status' => 'In Use', 'purchase_cost' => 12000000, 'current_value' => 9500000]);
        $dumpTruck = Equipment::create(['company_id' => $companyId, 'name' => 'Dump Truck Volvo A40', 'equipment_type' => 'Dump Truck', 'status' => 'Under Maintenance', 'purchase_cost' => 9800000, 'current_value' => 7100000]);
        $drillRig = Equipment::create(['company_id' => $companyId, 'name' => 'Drill Rig Atlas Copco', 'equipment_type' => 'Drill Rig', 'status' => 'Out of Service', 'purchase_cost' => 15000000, 'current_value' => 10200000]);

        $fuelOperator = Employee::create([
            'company_id' => $companyId, 'full_name' => 'Samuel Kiptoo', 'phone_number' => '+254712000103',
            'email' => 'samuel.kiptoo@riftvalleyquarries.example', 'national_id' => '30' . rand(100000, 999999),
            'license_number' => 'DL' . rand(100000, 999999), 'license_expiry' => now()->addYears(2),
            'emergency_contact_name' => 'Mary Kiptoo', 'emergency_contact_phone' => '+254712000199',
            'status' => 'Active', 'date_hired' => now()->subYears(3),
        ]);

        foreach ([$loader, $excavator, $dumpTruck] as $index => $equipment) {
            for ($i = 13; $i >= 0; $i -= 3) {
                FuelLog::create([
                    'company_id' => $companyId, 'equipment_id' => $equipment->id, 'employee_id' => $fuelOperator->id,
                    'date' => now()->subDays($i), 'quantity_litres' => rand(80, 180), 'cost_per_litre' => 165,
                    'total_cost' => rand(80, 180) * 165, 'fuel_station' => 'On-site Fuel Station',
                ]);
            }
        }

        Maintenance::create([
            'company_id' => $companyId, 'equipment_id' => $dumpTruck->id, 'maintenance_type' => 'Repair',
            'description' => 'Hydraulic system repair', 'cost' => 145000, 'maintenance_date' => now()->subDays(2),
            'status' => 'Pending', 'next_maintenance_date' => now()->addDays(3),
        ]);
        Maintenance::create([
            'company_id' => $companyId, 'equipment_id' => $loader->id, 'maintenance_type' => 'Service',
            'description' => 'Scheduled 500-hour service', 'cost' => 62000, 'maintenance_date' => now()->subDays(20),
            'status' => 'Completed', 'next_maintenance_date' => now()->addDays(5),
        ]);
        Maintenance::create([
            'company_id' => $companyId, 'equipment_id' => $excavator->id, 'maintenance_type' => 'Inspection',
            'description' => 'Undercarriage inspection', 'cost' => 18000, 'maintenance_date' => now()->subDays(1),
            'status' => 'Completed',
        ]);

        // Safety incidents (quarry-linked via extraction_site_id)
        SafetyIncident::create([
            'company_id' => $companyId, 'extraction_site_id' => $northSite->id, 'date' => now()->subDays(18),
            'severity' => 'Medium', 'status' => 'Closed', 'incident_category' => 'Slip / Trip',
            'description' => 'Worker slipped near the crusher feed area, minor bruising.',
            'injury_classification' => 'First Aid Case', 'employment_type' => 'Staff',
        ]);
        SafetyIncident::create([
            'company_id' => $companyId, 'extraction_site_id' => $southSite->id, 'date' => now()->subDays(4),
            'severity' => 'Low', 'status' => 'Under Investigation', 'incident_category' => 'Equipment Accident',
            'description' => 'Near-miss with reversing haul truck at the loading area.',
            'injury_classification' => 'No Injury', 'employment_type' => 'Staff',
        ]);

        // Revenue: paid customer payments over the last month
        for ($i = 0; $i < 8; $i++) {
            Payment::create([
                'company_id' => $companyId, 'client_id' => $customers[array_rand($customers)]->id,
                'amount' => rand(45000, 220000), 'payment_method' => 'Bank Transfer', 'status' => 'Paid',
                'invoice_number' => 'QRY-PAY-' . str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'payment_date' => now()->subDays(rand(0, 28)),
            ]);
        }

        $this->command?->info("QuarryDemoSeeder: demo company \"{$company->name}\" created (id {$companyId}).");
        $this->command?->info('Login: quarry.admin@riftvalleyquarries.example / password');
    }
}
