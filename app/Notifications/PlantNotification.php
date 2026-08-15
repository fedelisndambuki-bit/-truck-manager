<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlantNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: plant_stopped | crusher_breakdown | screen_failure |
     *        conveyor_failure | maintenance_due | production_below_target |
     *        energy_usage_high | quality_test_failed | approval_pending
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
            ->action('View Crushing Plant Management', url('/admin/crushing-plant-management'));
    }

    protected function getTitle(): string
    {
        return match ($this->type) {
            'plant_stopped' => 'Plant Stopped',
            'crusher_breakdown' => 'Crusher Breakdown',
            'screen_failure' => 'Screen Failure',
            'conveyor_failure' => 'Conveyor Failure',
            'maintenance_due' => 'Maintenance Due',
            'production_below_target' => 'Production Below Target',
            'energy_usage_high' => 'High Energy Usage',
            'quality_test_failed' => 'Quality Test Failed',
            'approval_pending' => 'Approval Pending',
            default => 'Crushing Plant Management Alert',
        };
    }

    protected function getBody(): string
    {
        $ref = $this->data['reference'] ?? '';

        return match ($this->type) {
            'plant_stopped' => "Plant {$ref} has stopped running.",
            'crusher_breakdown' => "Crusher {$ref} has broken down.",
            'screen_failure' => "Screen {$ref} has failed.",
            'conveyor_failure' => "Conveyor {$ref} has failed.",
            'maintenance_due' => "Maintenance is due for {$ref}.",
            'production_below_target' => "Production run {$ref} finished below the target yield.",
            'energy_usage_high' => "Energy usage for {$ref} is unusually high today.",
            'quality_test_failed' => "Quality test {$ref} failed.",
            'approval_pending' => "Production run {$ref} has been awaiting approval for over 2 hours.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'plant_stopped' => 'heroicon-o-stop-circle',
            'crusher_breakdown', 'screen_failure', 'conveyor_failure' => 'heroicon-o-exclamation-triangle',
            'maintenance_due' => 'heroicon-o-wrench-screwdriver',
            'production_below_target' => 'heroicon-o-arrow-trending-down',
            'energy_usage_high' => 'heroicon-o-bolt',
            'quality_test_failed' => 'heroicon-o-beaker',
            'approval_pending' => 'heroicon-o-clock',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'plant_stopped', 'crusher_breakdown', 'screen_failure', 'conveyor_failure', 'quality_test_failed' => 'danger',
            'maintenance_due', 'production_below_target', 'energy_usage_high', 'approval_pending' => 'warning',
            default => 'info',
        };
    }
}
