<?php

namespace App\Policies;

use App\Models\Subcontractor;
use App\Models\User;

class SubcontractorPolicy
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
        return $this->allows($user, 'subcontractors.view');
    }

    public function view(User $user, Subcontractor $subcontractor): bool
    {
        return $this->allows($user, 'subcontractors.view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'subcontractors.create');
    }

    public function update(User $user, Subcontractor $subcontractor): bool
    {
        return $this->allows($user, 'subcontractors.edit');
    }

    public function delete(User $user, Subcontractor $subcontractor): bool
    {
        return $this->allows($user, 'subcontractors.delete');
    }

    public function approve(User $user, Subcontractor $subcontractor): bool
    {
        return $this->allows($user, 'subcontractors.approve');
    }

    public function award(User $user, Subcontractor $subcontractor): bool
    {
        return $this->allows($user, 'subcontractors.award');
    }

    public function certify(User $user, Subcontractor $subcontractor): bool
    {
        return $this->allows($user, 'subcontractors.certify');
    }

    public function evaluate(User $user, Subcontractor $subcontractor): bool
    {
        return $this->allows($user, 'subcontractors.evaluate');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'subcontractors.export');
    }
}
