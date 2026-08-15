<?php

namespace App\AI;

use App\Models\User;

class ContextBuilder
{
    public static function build(User $user)
    {
        if ($user->hasRole('admin')) {
            return "User role: Admin. Full fleet visibility.";
        }

        if ($user->hasRole('finance')) {
            return "User role: Finance. Access limited to financial data.";
        }

        return "User role: Driver. Only assigned truck data allowed.";
    }
}
