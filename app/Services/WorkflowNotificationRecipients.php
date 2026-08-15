<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class WorkflowNotificationRecipients
{
    /**
     * Users in a company who should receive Workflow & Approvals platform
     * alerts (e.g. a newly published workflow): Super Admins plus anyone
     * with manage responsibility over the engine itself.
     */
    public function forCompany(?int $companyId): Collection
    {
        return User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn (User $user) => $user->hasRole('Super Admin')
                || $user->can('workflow.manage'));
    }
}
