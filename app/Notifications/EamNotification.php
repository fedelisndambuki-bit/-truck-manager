<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EamNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: maintenance_due | breakdown_reported | inspection_overdue |
     *        warranty_expiring | low_spare_parts | fuel_theft_detected |
     *        equipment_offline | engine_fault_detected | calibration_due
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
            ->action('View Equipment & Asset Management', url('/admin/equipment-asset-management'));
    }

    protected function getTitle(): string
    {
        return match ($this->type) {
            'maintenance_due' => 'Maintenance Due',
            'breakdown_reported' => 'Breakdown Reported',
            'inspection_overdue' => 'Inspection Overdue',
            'warranty_expiring' => 'Warranty Expiring',
            'low_spare_parts' => 'Low Spare Parts',
            'fuel_theft_detected' => 'Possible Fuel Theft',
            'equipment_offline' => 'Equipment Offline',
            'engine_fault_detected' => 'Engine Fault Detected',
            'calibration_due' => 'Calibration Due',
            default => 'Equipment & Asset Management Alert',
        };
    }

    protected function getBody(): string
    {
        $ref = $this->data['reference'] ?? '';

        return match ($this->type) {
            'maintenance_due' => "Preventive maintenance is due for {$ref}.",
            'breakdown_reported' => "A breakdown has been reported for {$ref}.",
            'inspection_overdue' => "An inspection is overdue for {$ref}.",
            'warranty_expiring' => "The warranty on {$ref} is expiring soon.",
            'low_spare_parts' => "Spare part {$ref} is at or below its reorder level.",
            'fuel_theft_detected' => "Fuel consumption on {$ref} was abnormally high — possible theft.",
            'equipment_offline' => "{$ref} has not reported a telematics reading recently.",
            'engine_fault_detected' => "An engine fault code was recorded for {$ref}.",
            'calibration_due' => "Calibration is due for {$ref}.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'maintenance_due', 'calibration_due' => 'heroicon-o-wrench-screwdriver',
            'breakdown_reported', 'engine_fault_detected' => 'heroicon-o-exclamation-triangle',
            'inspection_overdue' => 'heroicon-o-clipboard-document-check',
            'warranty_expiring' => 'heroicon-o-shield-check',
            'low_spare_parts' => 'heroicon-o-cube',
            'fuel_theft_detected' => 'heroicon-o-beaker',
            'equipment_offline' => 'heroicon-o-signal-slash',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'breakdown_reported', 'engine_fault_detected', 'fuel_theft_detected', 'equipment_offline' => 'danger',
            'maintenance_due', 'inspection_overdue', 'warranty_expiring', 'low_spare_parts', 'calibration_due' => 'warning',
            default => 'info',
        };
    }
}
