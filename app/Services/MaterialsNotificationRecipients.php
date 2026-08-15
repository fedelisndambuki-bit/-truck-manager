<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class MaterialsNotificationRecipients
{
    /**
     * Users in a company who should receive Materials Management alerts:
     * Super Admins/Company Admins plus anyone holding a warehouse-relevant
     * role.
     */
    public function forCompany(?int $companyId): Collection
    {
        return User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn (User $user) => $user->hasAnyRole([
                'Super Admin', 'Company Admin', 'Warehouse Manager', 'Storekeeper', 'Inventory Controller',
            ]));
    }
}
