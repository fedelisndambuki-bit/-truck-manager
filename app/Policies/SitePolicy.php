<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    protected function allows(User $user, string $permission): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->hasPermissionTo($permission);
    }

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'sites.view');
    }

    public function view(User $user, Site $site): bool
    {
        return $this->allows($user, 'sites.view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'sites.create');
    }

    public function update(User $user, Site $site): bool
    {
        return $this->allows($user, 'sites.edit');
    }

    public function delete(User $user, Site $site): bool
    {
        return $this->allows($user, 'sites.delete');
    }

    public function approve(User $user, Site $site): bool
    {
        return $this->allows($user, 'sites.approve');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'sites.export');
    }

    public function assignResources(User $user, Site $site): bool
    {
        return $this->allows($user, 'sites.assign_resources');
    }

    public function manageInspections(User $user, Site $site): bool
    {
        return $this->allows($user, 'sites.manage_inspections');
    }

    public function manageSafety(User $user, Site $site): bool
    {
        return $this->allows($user, 'sites.manage_safety');
    }

    public function manageDocuments(User $user, Site $site): bool
    {
        return $this->allows($user, 'sites.manage_documents');
    }

    public function manageReports(User $user, Site $site): bool
    {
        return $this->allows($user, 'sites.manage_reports');
    }
}
