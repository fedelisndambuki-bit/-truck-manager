<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;

class EquipmentPolicy
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
        return $this->allows($user, 'equipment.view');
    }

    public function view(User $user, Equipment $equipment): bool
    {
        return $this->allows($user, 'equipment.view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'equipment.create');
    }

    public function update(User $user, Equipment $equipment): bool
    {
        return $this->allows($user, 'equipment.edit');
    }

    public function delete(User $user, Equipment $equipment): bool
    {
        return $this->allows($user, 'equipment.delete');
    }

    public function approve(User $user, Equipment $equipment): bool
    {
        return $this->allows($user, 'equipment.approve');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'equipment.export');
    }

    public function transfer(User $user, Equipment $equipment): bool
    {
        return $this->allows($user, 'equipment.transfer');
    }

    public function manage(User $user, Equipment $equipment): bool
    {
        return $this->allows($user, 'equipment.manage');
    }
}
