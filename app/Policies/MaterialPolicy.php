<?php

namespace App\Policies;

use App\Models\Material;
use App\Models\User;

class MaterialPolicy
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
        return $this->allows($user, 'materials.view');
    }

    public function view(User $user, Material $material): bool
    {
        return $this->allows($user, 'materials.view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'materials.create');
    }

    public function update(User $user, Material $material): bool
    {
        return $this->allows($user, 'materials.edit');
    }

    public function delete(User $user, Material $material): bool
    {
        return $this->allows($user, 'materials.delete');
    }

    public function approve(User $user, Material $material): bool
    {
        return $this->allows($user, 'materials.approve');
    }

    public function issue(User $user, Material $material): bool
    {
        return $this->allows($user, 'materials.issue');
    }

    public function transfer(User $user, Material $material): bool
    {
        return $this->allows($user, 'materials.transfer');
    }

    public function adjust(User $user, Material $material): bool
    {
        return $this->allows($user, 'materials.adjust');
    }

    public function count(User $user, Material $material): bool
    {
        return $this->allows($user, 'materials.count');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'materials.export');
    }
}
