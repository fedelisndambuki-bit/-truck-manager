<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeighbridgeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: truck_checked_in | truck_waiting_too_long | loading_started |
     *        loading_completed | truck_returned_for_reload |
     *        weight_outside_tolerance | dispatch_approved | vehicle_exited |
     *        stock_running_low | scale_offline
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
            ->action('View Smart Weighbridge', url('/admin/smart-weighbridge'));
    }

    protected function getTitle(): string
    {
        return match ($this->type) {
            'truck_checked_in' => 'Truck Checked In',
            'truck_waiting_too_long' => 'Truck Waiting Too Long',
            'loading_started' => 'Loading Started',
            'loading_completed' => 'Loading Completed',
            'truck_returned_for_reload' => 'Truck Returned For Reload',
            'weight_outside_tolerance' => 'Weight Outside Tolerance',
            'dispatch_approved' => 'Dispatch Approved',
            'vehicle_exited' => 'Vehicle Exited',
            'stock_running_low' => 'Stock Running Low',
            'scale_offline' => 'Scale Offline',
            default => 'Smart Weighbridge Alert',
        };
    }

    protected function getBody(): string
    {
        $ref = $this->data['reference'] ?? '';

        return match ($this->type) {
            'truck_checked_in' => "Truck {$ref} has checked in at the gate.",
            'truck_waiting_too_long' => "Truck {$ref} has been waiting longer than the configured threshold.",
            'loading_started' => "Loading has started for {$ref}.",
            'loading_completed' => "Loading has completed for {$ref} — truck moved to Awaiting Gross Weight.",
            'truck_returned_for_reload' => "Truck {$ref} was returned to the loader for a top-up (underweight).",
            'weight_outside_tolerance' => "Dispatch {$ref} is outside the configured weight tolerance.",
            'dispatch_approved' => "Dispatch {$ref} has been approved and released.",
            'vehicle_exited' => "Vehicle for dispatch {$ref} has exited the quarry.",
            'stock_running_low' => "Stockpile {$ref} is running low.",
            'scale_offline' => "Weighbridge {$ref} is currently offline.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'truck_checked_in' => 'heroicon-o-identification',
            'truck_waiting_too_long' => 'heroicon-o-clock',
            'loading_started' => 'heroicon-o-play',
            'loading_completed' => 'heroicon-o-check-circle',
            'truck_returned_for_reload' => 'heroicon-o-arrow-path',
            'weight_outside_tolerance' => 'heroicon-o-exclamation-triangle',
            'dispatch_approved' => 'heroicon-o-clipboard-document-check',
            'vehicle_exited' => 'heroicon-o-arrow-right-on-rectangle',
            'stock_running_low' => 'heroicon-o-archive-box',
            'scale_offline' => 'heroicon-o-signal-slash',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'loading_completed', 'dispatch_approved', 'vehicle_exited' => 'success',
            'truck_checked_in', 'loading_started' => 'info',
            'truck_waiting_too_long', 'truck_returned_for_reload', 'stock_running_low' => 'warning',
            'weight_outside_tolerance', 'scale_offline' => 'danger',
            default => 'info',
        };
    }
}
