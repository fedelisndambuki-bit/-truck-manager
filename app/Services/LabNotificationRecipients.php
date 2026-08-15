<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class LabNotificationRecipients
{
    /**
     * Users in a company who should receive Laboratory & Quality Control
     * alerts: Super Admins/Company Admins plus anyone holding a
     * lab-relevant role, per spec ("Notify: Laboratory Technician, Quality
     * Manager, Production Manager, Dispatch Manager, Commercial Manager").
     */
    public function forCompany(?int $companyId): Collection
    {
        return User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn (User $user) => $user->hasAnyRole([
                'Super Admin', 'Company Admin', 'Quarry Manager',
                'Laboratory Manager', 'Laboratory Technician', 'Quality Manager',
                'Production Manager', 'Dispatch Manager', 'Commercial Manager',
            ]));
    }
}
