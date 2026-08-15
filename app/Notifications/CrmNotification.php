<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CrmNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: lead_assigned | quotation_expiring | quotation_accepted |
     *        credit_limit_exceeded
     */
    public function __construct(
        protected string $type,
        protected array $data = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return array_merge($this->data, [
            'type' => $this->type,
            'icon' => $this->getIcon(),
            'title' => $this->getTitle(),
            'body' => $this->getBody(),
            'color' => $this->getColor(),
            'url' => $this->data['url'] ?? null,
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getTitle())
            ->line($this->getBody())
            ->action('View CRM & Sales', url('/admin/crm-sales'));
    }

    protected function getTitle(): string
    {
        return match ($this->type) {
            'lead_assigned' => 'New Lead Assigned',
            'quotation_expiring' => 'Quotation Expiring',
            'quotation_accepted' => 'Quotation Accepted',
            'credit_limit_exceeded' => 'Credit Limit Exceeded',
            default => 'CRM & Sales Alert',
        };
    }

    protected function getBody(): string
    {
        $ref = $this->data['reference'] ?? '';

        return match ($this->type) {
            'lead_assigned' => "Lead {$ref} has been assigned to you.",
            'quotation_expiring' => "Quotation {$ref} is expiring soon.",
            'quotation_accepted' => "Quotation {$ref} has been accepted.",
            'credit_limit_exceeded' => "Accepting quotation {$ref} was blocked — it would exceed the customer's credit limit.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'lead_assigned' => 'heroicon-o-megaphone',
            'quotation_expiring' => 'heroicon-o-clock',
            'quotation_accepted' => 'heroicon-o-check-circle',
            'credit_limit_exceeded' => 'heroicon-o-exclamation-triangle',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'credit_limit_exceeded' => 'danger',
            'quotation_expiring' => 'warning',
            'quotation_accepted' => 'success',
            default => 'info',
        };
    }
}
