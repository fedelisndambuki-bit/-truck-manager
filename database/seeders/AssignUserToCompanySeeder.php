<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;

class AssignUserToCompanySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();
        if (!$user) {
            return;
        }

        $company = Company::query()->first();
        if (!$company) {
            $company = Company::create([]);
        }

        $user->company_id = $company->id;
        $user->save();
    }
}

