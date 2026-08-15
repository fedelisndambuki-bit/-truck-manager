<?php

namespace Database\Seeders;

use App\Models\EquipmentCategory;
use Illuminate\Database\Seeder;

/**
 * Reference-data taxonomy for the Equipment & Asset Management module —
 * global categories (company_id null) available to every company, not
 * demo/company-specific data, so this runs from DatabaseSeeder rather than
 * a standalone demo seeder.
 */
class EquipmentCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Excavators', 'Wheel Loaders', 'Bulldozers', 'Dump Trucks', 'Haul Trucks',
            'Drilling Rigs', 'Jaw Crushers', 'Cone Crushers', 'Impact Crushers', 'Screens',
            'Conveyors', 'Stackers', 'Feeders', 'Generators', 'Compressors', 'Water Pumps',
            'Water Bowsers', 'Forklifts', 'Mobile Crushers', 'Mobile Screens', 'Light Vehicles',
            'Service Trucks', 'Workshop Equipment',
        ];

        foreach ($categories as $name) {
            EquipmentCategory::withoutGlobalScope('company')->firstOrCreate(
                ['code' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name, 'is_custom' => false, 'company_id' => null]
            );
        }

        EquipmentCategory::withoutGlobalScope('company')->firstOrCreate(
            ['code' => 'custom-equipment'],
            ['name' => 'Custom Equipment', 'is_custom' => true, 'company_id' => null]
        );
    }
}
