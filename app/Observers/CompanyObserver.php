<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\SystemLog;

class CompanyObserver
{
    public function created(Company $company): void
    {
        SystemLog::create([
            'company_id' => $company->id,
            'action' => 'company_created',
            'severity' => 'info',
            'description' => "Company '{$company->name}' was successfully registered on the platform.",
        ]);
    }

    public function updated(Company $company): void
    {
        if ($company->isDirty('status')) {
            SystemLog::create([
                'company_id' => $company->id,
                'action' => 'company_status_changed',
                'severity' => $company->status === 'active' ? 'info' : 'warning',
                'description' => "Company '{$company->name}' status changed to '{$company->status}'.",
            ]);
        }

        if ($company->isDirty('plan_id')) {
            SystemLog::create([
                'company_id' => $company->id,
                'action' => 'company_plan_changed',
                'severity' => 'info',
                'description' => "Company '{$company->name}' plan was updated.",
            ]);
        }
    }

    public function deleted(Company $company): void
    {
        SystemLog::create([
            'company_id' => null, // Company is being deleted
            'action' => 'company_deleted',
            'severity' => 'danger',
            'description' => "Company '{$company->name}' (ID: {$company->id}) was removed from the system.",
        ]);
    }
}
