<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: approval_pending | approval_approved | approval_rejected |
     *        approval_escalated | approval_delegated | sla_breach | workflow_published
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
            ->action('View in Workflow & Approvals', url('/admin/workflow-approvals/approval-requests'));
    }

    protected function getTitle(): string
    {
        return match ($this->type) {
            'approval_pending' => 'Approval Required',
            'approval_approved' => 'Request Approved',
            'approval_rejected' => 'Request Rejected',
            'approval_escalated' => 'Approval Escalated',
            'approval_delegated' => 'Approval Delegated',
            'sla_breach' => 'SLA Breach',
            'workflow_published' => 'Workflow Published',
            default => 'Workflow & Approvals Update',
        };
    }

    protected function getBody(): string
    {
        $ref = $this->data['reference'] ?? '';
        $title = $this->data['title'] ?? 'a request';

        return match ($this->type) {
            'approval_pending' => "You have a new approval request: {$title} ({$ref}).",
            'approval_approved' => "{$title} ({$ref}) has been approved.",
            'approval_rejected' => "{$title} ({$ref}) has been rejected.",
            'approval_escalated' => "{$title} ({$ref}) has been escalated to you.",
            'approval_delegated' => "{$title} ({$ref}) has been delegated to you.",
            'sla_breach' => "{$title} ({$ref}) has breached its SLA.",
            'workflow_published' => "Workflow \"{$ref}\" has been published.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'approval_pending' => 'heroicon-o-clock',
            'approval_approved' => 'heroicon-o-check-circle',
            'approval_rejected' => 'heroicon-o-x-circle',
            'approval_escalated' => 'heroicon-o-arrow-trending-up',
            'approval_delegated' => 'heroicon-o-user-plus',
            'sla_breach' => 'heroicon-o-exclamation-triangle',
            'workflow_published' => 'heroicon-o-rocket-launch',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'approval_approved', 'workflow_published' => 'success',
            'approval_rejected', 'sla_breach' => 'danger',
            'approval_escalated', 'approval_delegated' => 'warning',
            default => 'info',
        };
    }
}
