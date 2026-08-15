<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SafetyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: incident_reported | high_risk_hazard | inspection_due | corrective_action_overdue |
     *        training_expiring | ppe_replacement_due | emergency_drill_due | compliance_expiring
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
            'incident_reported' => 'Safety Incident Reported',
            'high_risk_hazard' => 'High Risk Hazard Identified',
            'inspection_due' => 'Safety Inspection Due',
            'corrective_action_overdue' => 'Corrective Action Overdue',
            'training_expiring' => 'Safety Training Expiring',
            'ppe_replacement_due' => 'PPE Replacement Due',
            'emergency_drill_due' => 'Emergency Drill Due',
            'compliance_expiring' => 'Compliance Item Expiring',
            default => 'Safety Update',
        };
    }

    protected function getBody(): string
    {
        $site = $this->data['site_name'] ?? 'a site';
        $ref = $this->data['reference'] ?? '';

        return match ($this->type) {
            'incident_reported' => "Incident {$ref} was reported at {$site}.",
            'high_risk_hazard' => "A high/critical risk hazard ({$ref}) was identified at {$site}.",
            'inspection_due' => "Safety inspection {$ref} is due at {$site}.",
            'corrective_action_overdue' => "Corrective action {$ref} is overdue at {$site}.",
            'training_expiring' => "Safety training for {$ref} is expiring soon.",
            'ppe_replacement_due' => "PPE item for {$ref} is due for replacement.",
            'emergency_drill_due' => "An emergency drill is due at {$site}.",
            'compliance_expiring' => "Compliance record {$ref} is expiring soon at {$site}.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'incident_reported' => 'heroicon-o-exclamation-triangle',
            'high_risk_hazard' => 'heroicon-o-fire',
            'inspection_due' => 'heroicon-o-clipboard-document-check',
            'corrective_action_overdue' => 'heroicon-o-wrench-screwdriver',
            'training_expiring' => 'heroicon-o-academic-cap',
            'ppe_replacement_due' => 'heroicon-o-shield-check',
            'emergency_drill_due' => 'heroicon-o-shield-exclamation',
            'compliance_expiring' => 'heroicon-o-scale',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'incident_reported', 'high_risk_hazard', 'corrective_action_overdue' => 'danger',
            'inspection_due', 'training_expiring', 'ppe_replacement_due', 'emergency_drill_due', 'compliance_expiring' => 'warning',
            default => 'info',
        };
    }
}
