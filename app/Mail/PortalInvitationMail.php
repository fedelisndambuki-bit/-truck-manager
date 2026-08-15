<?php

namespace App\Mail;

use App\Models\PortalInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PortalInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PortalInvitation $invitation) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve been invited to collaborate on Tanaki Hub',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.portal-invitation',
            with: [
                'acceptUrl' => url('/portal/invitations/' . $this->invitation->token),
                'role' => $this->invitation->role,
                'expiresAt' => $this->invitation->expires_at,
            ]
        );
    }
}
