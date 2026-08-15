<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DispatchNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: dispatch_scheduled | truck_assigned | truck_loading |
     *        truck_departed | truck_delayed | truck_arrived |
     *        delivery_completed | delivery_failed
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
            ->action('View Dispatch Management', url('/admin/dispatch-management'));
    }

    protected function getTitle(): string
    {
        return match ($this->type) {
            'dispatch_scheduled' => 'Dispatch Scheduled',
            'truck_assigned' => 'Truck Assigned',
            'truck_loading' => 'Truck Loading',
            'truck_departed' => 'Truck Departed',
            'truck_delayed' => 'Truck Delayed',
            'truck_arrived' => 'Truck Arrived',
            'delivery_completed' => 'Delivery Completed',
            'delivery_failed' => 'Delivery Failed',
            default => 'Dispatch Management Alert',
        };
    }

    protected function getBody(): string
    {
        $ref = $this->data['reference'] ?? '';

        return match ($this->type) {
            'dispatch_scheduled' => "Dispatch {$ref} has been scheduled and is awaiting truck/driver assignment.",
            'truck_assigned' => "A truck and/or driver has been assigned to dispatch {$ref}.",
            'truck_loading' => "Loading is in progress for dispatch {$ref}.",
            'truck_departed' => "Truck for dispatch {$ref} has departed the quarry.",
            'truck_delayed' => "Dispatch {$ref} has been marked as delayed.",
            'truck_arrived' => "Truck for dispatch {$ref} has arrived at the customer site.",
            'delivery_completed' => "Delivery for dispatch {$ref} has been confirmed complete.",
            'delivery_failed' => "Delivery for dispatch {$ref} was rejected or returned — proof of delivery flagged.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'dispatch_scheduled' => 'heroicon-o-calendar-days',
            'truck_assigned' => 'heroicon-o-truck',
            'truck_loading' => 'heroicon-o-arrow-up-tray',
            'truck_departed' => 'heroicon-o-arrow-right-on-rectangle',
            'truck_delayed' => 'heroicon-o-exclamation-triangle',
            'truck_arrived' => 'heroicon-o-map-pin',
            'delivery_completed' => 'heroicon-o-document-check',
            'delivery_failed' => 'heroicon-o-x-circle',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'truck_departed', 'truck_arrived', 'delivery_completed' => 'success',
            'dispatch_scheduled', 'truck_assigned', 'truck_loading' => 'info',
            'truck_delayed' => 'warning',
            'delivery_failed' => 'danger',
            default => 'info',
        };
    }
}
