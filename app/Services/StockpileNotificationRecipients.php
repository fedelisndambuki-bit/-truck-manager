<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class StockpileNotificationRecipients
{
    /**
     * Users in a company who should receive Stockpile Management alerts:
     * Super Admins/Company Admins plus anyone holding a stockpile-relevant role.
     */
    public function forCompany(?int $companyId): Collection
    {
        return User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn (User $user) => $user->hasAnyRole([
                'Super Admin', 'Company Admin', 'Quarry Manager', 'Inventory Controller',
                'Commercial Manager', 'Dispatch Supervisor', 'Quality Manager',
            ]));
    }
}
