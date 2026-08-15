<?php

namespace App\Observers;

use App\Models\User;
use App\Models\SystemLog;

class UserObserver
{
    public function created(User $user): void
    {
        SystemLog::create([
            'company_id' => $user->company_id,
            'action' => 'user_created',
            'severity' => 'info',
            'description' => "New user '{$user->email}' was created.",
        ]);

        // Driver auto-provisioning happens in AppServiceProvider via a
        // Spatie RoleAttached listener instead of here — role assignment is
        // a pivot attach and does not fire this created() hook at all when
        // the role is assigned after the user is created (the common case).
    }

    public function updated(User $user): void
    {
        if ($user->isDirty('email')) {
            SystemLog::create([
                'company_id' => $user->company_id,
                'action' => 'user_email_changed',
                'severity' => 'warning',
                'description' => "User ID {$user->id} changed email to '{$user->email}'.",
            ]);
        }

        if ($user->isDirty('locked_until')) {
            $isLocked = !empty($user->locked_until) && $user->locked_until->isFuture();
            SystemLog::create([
                'company_id' => $user->company_id,
                'action' => $isLocked ? 'user_locked' : 'user_unlocked',
                'severity' => $isLocked ? 'warning' : 'info',
                'description' => "User '{$user->email}' was " . ($isLocked ? "locked until {$user->locked_until}" : "unlocked") . ".",
            ]);
        }
    }

    public function deleted(User $user): void
    {
        SystemLog::create([
            'company_id' => $user->company_id,
            'action' => 'user_deleted',
            'severity' => 'danger',
            'description' => "User '{$user->email}' (ID: {$user->id}) was deleted.",
        ]);
    }
}
