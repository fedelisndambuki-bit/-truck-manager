<?php

namespace App\Services;

use App\Mail\PortalInvitationMail;
use App\Models\ExternalOrganization;
use App\Models\PortalInvitation;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PortalInvitationService
{
    public function invite(string $email, ?ExternalOrganization $organization, ?Project $project, ?string $role): PortalInvitation
    {
        $invitation = PortalInvitation::create([
            'company_id' => Auth::user()?->company_id,
            'email' => strtolower(trim($email)),
            'external_organization_id' => $organization?->id,
            'invited_by_id' => Auth::id(),
            'project_id' => $project?->id,
            'role' => $role,
        ]);

        Mail::to($invitation->email)->send(new PortalInvitationMail($invitation));

        return $invitation;
    }

    public function acceptUrl(PortalInvitation $invitation): string
    {
        return url('/portal/invitations/' . $invitation->token);
    }
}
