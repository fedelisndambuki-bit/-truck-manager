<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccessRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Applicant-facing (sent via on-demand mail route before a User exists):
     *   verify_email | request_received | additional_info_required |
     *   approved | rejected
     *
     * SuperAdmin-facing (sent to real Users, both channels):
     *   new_access_request | info_submitted | sla_breach
     */
    public function __construct(
        protected string $type,
        protected array $data = []
    ) {}

    public function via(object $notifiable): array
    {
        // An applicant pre-approval has no User row yet — they're notified
        // via Notification::route('mail', $email), which can't back a
        // 'database' channel entry (no notifiable model to attach it to).
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return array_merge($this->data, [
            'type'  => $this->type,
            'icon'  => $this->getIcon(),
            'title' => $this->getTitle(),
            'body'  => $this->getBody(),
            'color' => $this->getColor(),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->getEmailSubject())
            ->greeting('Hello ' . ($this->data['requester_name'] ?? $this->data['name'] ?? '') . ',');

        foreach ($this->getEmailLines() as $line) {
            $mail->line($line);
        }

        if ($url = $this->getActionUrl()) {
            $mail->action($this->getActionText(), $url);
        }

        return $mail->line('Thank you for your interest in Tanaki Hub!');
    }

    protected function getTitle(): string
    {
        return match ($this->type) {
            'verify_email'             => 'Verify Your Email',
            'request_received'        => 'Access Request Received',
            'additional_info_required' => 'Additional Information Required',
            'approved'                 => '🎉 Your Tanaki Hub Access Has Been Approved',
            'rejected'                 => 'Access Request Update',
            'new_access_request'       => '📥 New Access Request',
            'info_submitted'           => 'Applicant Responded to Information Request',
            'sla_breach'               => '⏰ Access Request Awaiting Review',
            default                    => 'Access Request Update',
        };
    }

    protected function getBody(): string
    {
        $org = $this->data['organization_name'] ?? 'the applicant';

        return match ($this->type) {
            'new_access_request' => "New access request from {$org}.",
            'info_submitted'     => "{$org} responded to your information request.",
            'sla_breach'         => "A request from {$org} has been awaiting review for over " . ($this->data['sla_hours'] ?? 48) . ' hours.',
            default               => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'new_access_request' => 'heroicon-o-inbox-arrow-down',
            'info_submitted'     => 'heroicon-o-chat-bubble-left-right',
            'sla_breach'         => 'heroicon-o-clock',
            default               => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'sla_breach' => 'warning',
            default       => 'info',
        };
    }

    protected function getEmailSubject(): string
    {
        return match ($this->type) {
            'verify_email'             => 'Verify your email — Tanaki Hub Access Request',
            'request_received'        => 'We\'ve received your Tanaki Hub access request',
            'additional_info_required' => 'Additional information needed for your Tanaki Hub request',
            'approved'                 => '🎉 Your Tanaki Hub workspace is ready',
            'rejected'                 => 'Update on your Tanaki Hub access request',
            'new_access_request'       => 'New Tanaki Hub access request: ' . ($this->data['organization_name'] ?? ''),
            'info_submitted'           => 'Applicant responded — access request follow-up',
            'sla_breach'               => 'Access request pending review — SLA exceeded',
            default                    => 'Tanaki Hub Access Request Update',
        };
    }

    protected function getEmailLines(): array
    {
        $org = $this->data['organization_name'] ?? 'your organization';

        return match ($this->type) {
            'verify_email' => [
                "Thanks for requesting access to Tanaki Hub for **{$org}**.",
                'Please verify your email address to continue — this link expires soon, so please use it promptly.',
            ],
            'request_received' => [
                'Your email has been verified and your organization request is now being reviewed.',
                'Tanaki Hub will contact you if additional information is required.',
            ],
            'additional_info_required' => [
                'We need a bit more information to continue reviewing your access request:',
                '"' . ($this->data['message'] ?? '') . '"',
                'Please reply with these details so we can continue the review.',
            ],
            'approved' => [
                "Great news — your Tanaki Hub access request for **{$org}** has been approved!",
                'Use the link below to set your password and access your new workspace.',
            ],
            'rejected' => [
                'Your Tanaki Hub access request could not be approved at this time.',
                'If you believe this is in error, please contact support.',
            ],
            'new_access_request' => [
                "A new access request was submitted by **{$org}**.",
            ],
            'info_submitted' => [
                "**{$org}** replied to your information request. Please review their response.",
            ],
            'sla_breach' => [
                "A request from **{$org}** has been awaiting review for over " . ($this->data['sla_hours'] ?? 48) . ' hours.',
            ],
            default => ['Your access request status has been updated.'],
        };
    }

    protected function getActionUrl(): ?string
    {
        return match ($this->type) {
            'verify_email'             => $this->data['verification_url'] ?? null,
            'additional_info_required', 'rejected' => $this->data['status_url'] ?? null,
            'approved'                 => $this->data['activation_url'] ?? url('/admin/login'),
            'new_access_request', 'info_submitted', 'sla_breach' => url('/super-admin/access-requests'),
            default                    => null,
        };
    }

    protected function getActionText(): string
    {
        return match ($this->type) {
            'verify_email'             => 'Verify Email',
            'additional_info_required' => 'Respond Now',
            'approved'                 => 'Set Your Password',
            default                    => 'View Request',
        };
    }
}
