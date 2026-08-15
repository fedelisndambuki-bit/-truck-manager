<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class DiaryNotificationRecipients
{
    /**
     * Users in a company who should receive diary alerts: Super Admins plus anyone
     * with review/approval permissions over site diaries.
     */
    public function forCompany(?int $companyId): Collection
    {
        return User::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn (User $user) => $user->hasRole('Super Admin')
                || $user->can('site_diaries.review')
                || $user->can('site_diaries.approve'));
    }
}
