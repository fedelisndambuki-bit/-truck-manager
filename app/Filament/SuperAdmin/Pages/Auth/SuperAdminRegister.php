<?php

namespace App\Filament\SuperAdmin\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class SuperAdminRegister extends BaseRegister
{
    /**
     * @param array<string, mixed> $data
     */
    protected function handleRegistration(array $data): Model
    {
        $userClass = $this->getUserModel();
        
        $user = $userClass::create($data);

        $role = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => config('auth.defaults.guard', 'web'),
        ]);

        $user->assignRole($role);

        return $user;
    }
}
