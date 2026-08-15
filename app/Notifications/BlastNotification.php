<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BlastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: blast_scheduled | blast_approved | permit_expiring |
     *        explosive_stock_low | blast_delayed |
     *        environmental_threshold_exceeded | blast_completed |
     *        post_blast_analysis_pending
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
            ->action('View Blast Management', url('/admin/blast-management/blast-plans'));
    }

    protected function getTitle(): string
    {
        return match ($this->type) {
            'blast_scheduled' => 'Blast Scheduled',
            'blast_approved' => 'Blast Plan Approved',
            'permit_expiring' => 'Blast Permit Expiring',
            'explosive_stock_low' => 'Low Explosive Stock',
            'blast_delayed' => 'Blast Delayed',
            'environmental_threshold_exceeded' => 'Environmental Threshold Exceeded',
            'blast_completed' => 'Blast Completed',
            'post_blast_analysis_pending' => 'Post-Blast Analysis Pending',
            default => 'Blast Management Alert',
        };
    }

    protected function getBody(): string
    {
        $ref = $this->data['reference'] ?? '';

        return match ($this->type) {
            'blast_scheduled' => "Blast {$ref} has been scheduled.",
            'blast_approved' => "Blast plan {$ref} has been fully approved and is ready for execution.",
            'permit_expiring' => "The blast permit for {$ref} is expiring soon.",
            'explosive_stock_low' => "Explosive stock for {$ref} is running low.",
            'blast_delayed' => "Blast {$ref} has been delayed.",
            'environmental_threshold_exceeded' => "An environmental monitoring threshold was exceeded for blast {$ref}.",
            'blast_completed' => "Blast {$ref} has been completed.",
            'post_blast_analysis_pending' => "Post-blast analysis is still pending for blast {$ref}.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'blast_scheduled' => 'heroicon-o-calendar-days',
            'blast_approved' => 'heroicon-o-check-circle',
            'permit_expiring' => 'heroicon-o-document-text',
            'explosive_stock_low' => 'heroicon-o-beaker',
            'blast_delayed' => 'heroicon-o-pause-circle',
            'environmental_threshold_exceeded' => 'heroicon-o-globe-alt',
            'blast_completed' => 'heroicon-o-bolt',
            'post_blast_analysis_pending' => 'heroicon-o-magnifying-glass',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'blast_approved', 'blast_completed' => 'success',
            'permit_expiring', 'explosive_stock_low', 'blast_delayed', 'post_blast_analysis_pending' => 'warning',
            'environmental_threshold_exceeded' => 'danger',
            default => 'info',
        };
    }
}
