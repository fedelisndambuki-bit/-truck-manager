<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DiaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: diary_missing | diary_submitted | diary_approved | diary_rejected |
     *        severe_weather | high_delay | equipment_breakdown | material_shortage
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
            'diary_missing' => 'Diary Missing',
            'diary_submitted' => 'Diary Submitted',
            'diary_approved' => 'Diary Approved',
            'diary_rejected' => 'Diary Rejected',
            'severe_weather' => 'Severe Weather Alert',
            'high_delay' => 'High Impact Delay',
            'equipment_breakdown' => 'Equipment Breakdown',
            'material_shortage' => 'Material Shortage',
            default => 'Diary Update',
        };
    }

    protected function getBody(): string
    {
        $site = $this->data['site_name'] ?? 'a site';
        $diary = $this->data['diary_number'] ?? '';

        return match ($this->type) {
            'diary_missing' => "No daily site diary has been logged for {$site} yesterday.",
            'diary_submitted' => "Diary {$diary} for {$site} was submitted and is awaiting review.",
            'diary_approved' => "Diary {$diary} for {$site} has been approved.",
            'diary_rejected' => "Diary {$diary} for {$site} was rejected: " . ($this->data['reason'] ?? ''),
            'severe_weather' => "Severe weather logged for {$site} in diary {$diary}.",
            'high_delay' => "A high-impact delay was logged for {$site} in diary {$diary}.",
            'equipment_breakdown' => "Equipment breakdown reported at {$site} in diary {$diary}.",
            'material_shortage' => "Material shortage reported at {$site} in diary {$diary}.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'diary_missing' => 'heroicon-o-exclamation-circle',
            'diary_submitted' => 'heroicon-o-paper-airplane',
            'diary_approved' => 'heroicon-o-check-circle',
            'diary_rejected' => 'heroicon-o-x-circle',
            'severe_weather' => 'heroicon-o-cloud',
            'high_delay' => 'heroicon-o-exclamation-triangle',
            'equipment_breakdown' => 'heroicon-o-wrench-screwdriver',
            'material_shortage' => 'heroicon-o-cube',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'diary_missing' => 'danger',
            'diary_submitted' => 'info',
            'diary_approved' => 'success',
            'diary_rejected' => 'danger',
            'severe_weather' => 'danger',
            'high_delay' => 'warning',
            'equipment_breakdown' => 'danger',
            'material_shortage' => 'warning',
            default => 'info',
        };
    }
}
