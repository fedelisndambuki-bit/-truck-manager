<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
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
        return $this->allows($user, 'projects.view');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->allows($user, 'projects.view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        return $this->allows($user, 'projects.edit');
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->allows($user, 'projects.delete');
    }

    public function approve(User $user, Project $project): bool
    {
        return $this->allows($user, 'projects.approve');
    }

    public function archive(User $user, Project $project): bool
    {
        return $this->allows($user, 'projects.archive');
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'projects.export');
    }

    public function manage(User $user, Project $project): bool
    {
        return $this->allows($user, 'projects.manage');
    }
}
