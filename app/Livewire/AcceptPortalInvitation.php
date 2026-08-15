<?php

namespace App\Livewire;

use App\Models\ExternalAccessLog;
use App\Models\PortalInvitation;
use App\Models\ProjectUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class AcceptPortalInvitation extends Component
{
    public string $token;

    public ?PortalInvitation $invitation = null;

    public string $name = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $invalid = false;

    public bool $accepted = false;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->invitation = PortalInvitation::withoutGlobalScope('company')->where('token', $token)->first();

        if (! $this->invitation || $this->invitation->isExpired() || $this->invitation->isAccepted()) {
            $this->invalid = true;
        }
    }

    public function accept(): void
    {
        if ($this->invalid || ! $this->invitation) {
            return;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'company_id' => $this->invitation->company_id,
            'name' => $this->name,
            'email' => $this->invitation->email,
            'password' => $this->password,
            'user_type' => 'external',
            'external_organization_id' => $this->invitation->external_organization_id,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        if ($this->invitation->role && Role::where('name', $this->invitation->role)->where('guard_name', 'web')->exists()) {
            $user->assignRole($this->invitation->role);
        }

        if ($this->invitation->project_id) {
            ProjectUser::create([
                'company_id' => $this->invitation->company_id,
                'user_id' => $user->id,
                'project_id' => $this->invitation->project_id,
                'role' => $this->invitation->role,
                'status' => 'Active',
                'start_date' => now(),
                'invited_by_id' => $this->invitation->invited_by_id,
                'invited_at' => $this->invitation->created_at,
            ]);
        }

        $this->invitation->update(['accepted_at' => now()]);

        Auth::login($user);
        ExternalAccessLog::record($user, 'login', ['via' => 'portal_invitation_accept']);

        $this->accepted = true;

        $this->redirect('/admin', navigate: false);
    }

    public function render()
    {
        return view('livewire.accept-portal-invitation');
    }
}
