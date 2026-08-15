<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DriverNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: emergency_sos
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
            'emergency_sos' => 'Driver SOS Alert',
            default => 'Driver Update',
        };
    }

    protected function getBody(): string
    {
        $driver = $this->data['driver_name'] ?? 'A driver';

        return match ($this->type) {
            'emergency_sos' => "{$driver} triggered an SOS alert" . (isset($this->data['location']) ? " near {$this->data['location']}." : '.'),
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'emergency_sos' => 'heroicon-o-exclamation-triangle',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'emergency_sos' => 'danger',
            default => 'info',
        };
    }
}
