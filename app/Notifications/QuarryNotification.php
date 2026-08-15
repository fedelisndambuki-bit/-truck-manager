<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuarryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: low_stockpile | equipment_breakdown | plant_breakdown |
     *        maintenance_due | low_fuel | delayed_dispatch |
     *        production_below_target | low_yield | target_achieved |
     *        shift_completed | approval_pending
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
            ->action('View Quarry Dashboard', url('/admin/quarry-dashboard'));
    }

    protected function getTitle(): string
    {
        return match ($this->type) {
            'low_stockpile' => 'Low Stockpile Alert',
            'equipment_breakdown' => 'Equipment Breakdown',
            'plant_breakdown' => 'Crushing Plant Breakdown',
            'maintenance_due' => 'Maintenance Due',
            'low_fuel' => 'Low Fuel Stock',
            'delayed_dispatch' => 'Delayed Dispatch',
            'production_below_target' => 'Production Below Target',
            'low_yield' => 'Low Yield Alert',
            'target_achieved' => 'Production Target Achieved',
            'shift_completed' => 'Shift Completed',
            'approval_pending' => 'Approval Pending',
            default => 'Quarry Operations Alert',
        };
    }

    protected function getBody(): string
    {
        $ref = $this->data['reference'] ?? '';

        return match ($this->type) {
            'low_stockpile' => "Stockpile {$ref} has fallen below its minimum level.",
            'equipment_breakdown' => "Equipment {$ref} is marked Out of Service.",
            'plant_breakdown' => "Crushing plant {$ref} is marked Breakdown.",
            'maintenance_due' => "Maintenance is due for {$ref}.",
            'low_fuel' => "Fuel stock at {$ref} is running low.",
            'delayed_dispatch' => "Weighbridge ticket {$ref} has been open longer than expected.",
            'production_below_target' => "Production at {$ref} is below its target for the period.",
            'low_yield' => "Production record {$ref} recorded a yield below the configured threshold.",
            'target_achieved' => "Production target for {$ref} has been achieved.",
            'shift_completed' => "Shift production for {$ref} has been logged.",
            'approval_pending' => "Production record {$ref} is awaiting your approval.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'low_stockpile' => 'heroicon-o-archive-box',
            'equipment_breakdown', 'plant_breakdown' => 'heroicon-o-exclamation-circle',
            'maintenance_due' => 'heroicon-o-wrench',
            'low_fuel' => 'heroicon-o-beaker',
            'delayed_dispatch' => 'heroicon-o-clock',
            'production_below_target' => 'heroicon-o-arrow-trending-down',
            'low_yield' => 'heroicon-o-chart-pie',
            'target_achieved' => 'heroicon-o-flag',
            'shift_completed' => 'heroicon-o-check-circle',
            'approval_pending' => 'heroicon-o-paper-airplane',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'equipment_breakdown', 'plant_breakdown' => 'danger',
            'low_stockpile', 'low_fuel', 'delayed_dispatch', 'production_below_target', 'low_yield' => 'warning',
            'target_achieved', 'shift_completed' => 'success',
            default => 'info',
        };
    }
}
