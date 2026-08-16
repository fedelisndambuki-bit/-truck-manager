<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class AIPolicy
{
    /**
     * Determine whether the user can access AI features. Uses the
     * 'access-ai' Spatie permission (granted to Super Admin/Company
     * Admin/Fleet Manager by RoleSeeder, same as before) rather than a
     * hardcoded role list, so it can also be granted to other roles (e.g.
     * Finance Manager) — the per-module AiDataProviderInterface permission
     * checks then decide what data any of them can actually see.
     */
    public function accessAI(User $user): Response
    {
        if ($user->hasRole('Super Admin') || $user->can('access-ai')) {
            return Response::allow();
        }

        return Response::deny('You do not have permission to access AI features.');
    }

    /**
     * Determine whether the user can view AI logs.
     */
    public function viewLogs(User $user): Response
    {
        // Only Super Admin and Company Admin can view logs
        if ($user->hasRole(['Super Admin', 'Company Admin'])) {
            return Response::allow();
        }

        return Response::deny('Only administrators can view AI logs.');
    }

    /**
     * Determine whether the user can delete AI logs.
     */
    public function deleteLogs(User $user): Response
    {
        // Only Super Admin can delete logs
        if ($user->hasRole('Super Admin')) {
            return Response::allow();
        }

        return Response::deny('Only Super Admin can delete AI logs.');
    }
}
