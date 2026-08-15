<?php

namespace App\Policies;

use App\Models\Staff;
use App\Models\User;

class StaffPolicy
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
        return $this->allows($user, 'hr.view');
    }

    public function view(User $user, Staff $staff): bool
    {
        return $this->allows($user, 'hr.view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'hr.create');
    }

    public function update(User $user, Staff $staff): bool
    {
        return $this->allows($user, 'hr.edit');
    }

    public function delete(User $user, Staff $staff): bool
    {
        return $this->allows($user, 'hr.delete');
    }

    public function approve(User $user, Staff $staff): bool
    {
        return $this->allows($user, 'hr.approve');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'hr.export');
    }

    public function payrollAccess(User $user): bool
    {
        return $this->allows($user, 'hr.payroll_access');
    }
}
