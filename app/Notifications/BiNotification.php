<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BiNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: scheduled_report_ready | kpi_threshold_exceeded | budget_risk |
     *        safety_risk | project_delay | forecast_changed
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
            'scheduled_report_ready' => 'Scheduled Report Ready',
            'kpi_threshold_exceeded' => 'KPI Threshold Exceeded',
            'budget_risk' => 'Budget Risk Detected',
            'safety_risk' => 'Rising Safety Risk',
            'project_delay' => 'Project Delay Detected',
            'forecast_changed' => 'Forecast Changed',
            default => 'Business Intelligence Alert',
        };
    }

    protected function getBody(): string
    {
        $project = $this->data['project_name'] ?? 'a project';
        $ref = $this->data['reference'] ?? '';

        return match ($this->type) {
            'scheduled_report_ready' => "Your scheduled report \"{$ref}\" has been generated and delivered.",
            'kpi_threshold_exceeded' => "{$ref} has crossed its configured alert threshold.",
            'budget_risk' => "{$project} is trending over budget ({$ref}).",
            'safety_risk' => "Safety incidents are rising this month ({$ref}).",
            'project_delay' => "{$project} is behind schedule ({$ref}).",
            'forecast_changed' => "The forecast for {$project} has changed significantly ({$ref}).",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'scheduled_report_ready' => 'heroicon-o-document-check',
            'kpi_threshold_exceeded' => 'heroicon-o-chart-bar',
            'budget_risk' => 'heroicon-o-banknotes',
            'safety_risk' => 'heroicon-o-shield-exclamation',
            'project_delay' => 'heroicon-o-clock',
            'forecast_changed' => 'heroicon-o-presentation-chart-line',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'budget_risk', 'safety_risk', 'project_delay' => 'danger',
            'kpi_threshold_exceeded', 'forecast_changed' => 'warning',
            'scheduled_report_ready' => 'success',
            default => 'info',
        };
    }
}
