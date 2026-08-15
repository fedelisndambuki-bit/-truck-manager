<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class AIPolicy
{
    /**
     * Determine whether the user can access AI features.
     */
    public function accessAI(User $user): Response
    {
        // Only Super Admin, Company Admin, and Fleet Manager can access AI
        if ($user->hasRole(['Super Admin', 'Company Admin', 'Fleet Manager'])) {
            return Response::allow();
        }

        return Response::deny('Only administrators and fleet managers can access AI features.');
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
