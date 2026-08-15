<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DocumentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: document_uploaded | approval_required | revision_submitted | rfi_due |
     *        contract_expiring | drawing_updated | approval_completed | document_shared
     */
    public function __construct(
        protected string $type,
        protected array $data = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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

    protected function getTitle(): string
    {
        return match ($this->type) {
            'document_uploaded' => 'Document Uploaded',
            'approval_required' => 'Approval Required',
            'revision_submitted' => 'Revision Submitted',
            'rfi_due' => 'RFI Due',
            'contract_expiring' => 'Contract Expiring',
            'drawing_updated' => 'Drawing Updated',
            'approval_completed' => 'Approval Completed',
            'document_shared' => 'Document Shared',
            default => 'Document Management Update',
        };
    }

    protected function getBody(): string
    {
        $reference = $this->data['reference'] ?? '';

        return match ($this->type) {
            'document_uploaded' => "Document {$reference} was uploaded.",
            'approval_required' => "{$reference} is waiting for your approval.",
            'revision_submitted' => "A new revision was submitted for {$reference}.",
            'rfi_due' => "RFI {$reference} is due soon.",
            'contract_expiring' => "Contract {$reference} is expiring soon.",
            'drawing_updated' => "Drawing {$reference} was updated to a new revision.",
            'approval_completed' => "{$reference} has completed its approval workflow.",
            'document_shared' => "{$reference} was shared with {$this->data['shared_with']}.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'document_uploaded' => 'heroicon-o-arrow-up-tray',
            'approval_required' => 'heroicon-o-clock',
            'revision_submitted' => 'heroicon-o-arrow-path',
            'rfi_due' => 'heroicon-o-question-mark-circle',
            'contract_expiring' => 'heroicon-o-calendar',
            'drawing_updated' => 'heroicon-o-pencil-square',
            'approval_completed' => 'heroicon-o-check-badge',
            'document_shared' => 'heroicon-o-share',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'contract_expiring', 'rfi_due' => 'danger',
            'approval_required', 'revision_submitted', 'drawing_updated' => 'warning',
            'approval_completed', 'document_uploaded' => 'success',
            default => 'info',
        };
    }
}
