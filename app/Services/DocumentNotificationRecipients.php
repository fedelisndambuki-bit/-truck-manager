<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class DocumentNotificationRecipients
{
    /**
     * Users in a company who should receive Document Management alerts: Super
     * Admins plus anyone with approval/management responsibility over documents.
     */
    public function forCompany(?int $companyId): Collection
    {
        return User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn (User $user) => $user->hasRole('Super Admin')
                || $user->can('documents.approve')
                || $user->can('documents.manage'));
    }
}
