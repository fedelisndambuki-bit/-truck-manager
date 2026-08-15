<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class EamNotificationRecipients
{
    /**
     * Users in a company who should receive Equipment & Asset Management
     * alerts: Super Admins/Company Admins plus anyone holding an
     * EAM-relevant role.
     */
    public function forCompany(?int $companyId): Collection
    {
        return User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn (User $user) => $user->hasAnyRole([
                'Super Admin', 'Company Admin', 'Quarry Manager', 'Maintenance Manager',
                'Workshop Manager', 'Maintenance Technician', 'Fleet Manager', 'Inventory Controller',
            ]));
    }
}
