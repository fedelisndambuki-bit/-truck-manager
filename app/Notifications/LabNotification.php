<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LabNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: sample_received | testing_due | test_failed | quality_hold |
     *        quality_released | calibration_due | certificate_generated |
     *        approval_pending
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
            ->action('View Laboratory & Quality Control', url('/admin/laboratory-quality-control'));
    }

    protected function getTitle(): string
    {
        return match ($this->type) {
            'sample_received' => 'Sample Received',
            'testing_due' => 'Testing Due',
            'test_failed' => 'Test Failed',
            'quality_hold' => 'Quality Hold Placed',
            'quality_released' => 'Quality Hold Released',
            'calibration_due' => 'Calibration Due',
            'certificate_generated' => 'Certificate Generated',
            'approval_pending' => 'Quality Approval Pending',
            default => 'Laboratory & Quality Control Alert',
        };
    }

    protected function getBody(): string
    {
        $ref = $this->data['reference'] ?? '';

        return match ($this->type) {
            'sample_received' => "Sample {$ref} has been received by the laboratory.",
            'testing_due' => "Testing for sample {$ref} is due and has not yet started.",
            'test_failed' => "A test for sample {$ref} has returned a Fail result.",
            'quality_hold' => "A quality hold has been placed — {$ref}. Held stock is unavailable for dispatch.",
            'quality_released' => "Quality hold {$ref} has been released — stock is available again.",
            'calibration_due' => "Laboratory equipment {$ref} has a calibration due.",
            'certificate_generated' => "Certificate {$ref} has been generated.",
            'approval_pending' => "Sample {$ref} is awaiting quality approval.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'sample_received' => 'heroicon-o-inbox-arrow-down',
            'testing_due' => 'heroicon-o-clock',
            'test_failed' => 'heroicon-o-x-circle',
            'quality_hold' => 'heroicon-o-lock-closed',
            'quality_released' => 'heroicon-o-lock-open',
            'calibration_due' => 'heroicon-o-adjustments-horizontal',
            'certificate_generated' => 'heroicon-o-document-check',
            'approval_pending' => 'heroicon-o-clipboard-document-check',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'sample_received', 'quality_released', 'certificate_generated' => 'success',
            'testing_due', 'approval_pending' => 'info',
            'quality_hold', 'calibration_due' => 'warning',
            'test_failed' => 'danger',
            default => 'info',
        };
    }
}
