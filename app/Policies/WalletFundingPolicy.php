<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WalletFunding;
use Illuminate\Auth\Access\Response;

class WalletFundingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Filtered by MultiTenantScope
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, WalletFunding $walletFunding): bool
    {
        return $user->company_id === $walletFunding->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, WalletFunding $walletFunding): bool
    {
        return false; // Fundings are immutable once initiated
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WalletFunding $walletFunding): bool
    {
        return false;
    }
}
