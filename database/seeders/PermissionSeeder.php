<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'View Dashboard',
            'Manage Trucks',
            'Manage Drivers',
            'View Trips',
            'Create Trips',
            'Approve Settlements',
            'View Payments',
            'Export Reports',
            'Access Settings',
            'use ai assistant',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }
}