<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class CrmNotificationRecipients
{
    /**
     * Users in a company who should receive CRM & Sales alerts: Super
     * Admins/Company Admins plus anyone holding a commercial/sales role.
     */
    public function forCompany(?int $companyId): Collection
    {
        return User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn (User $user) => $user->hasAnyRole([
                'Super Admin', 'Company Admin', 'Commercial Director', 'Sales Manager', 'Account Manager',
            ]));
    }
}
