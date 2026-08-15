<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FinanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: budget_exceeded | invoice_overdue | retention_due | payment_received |
     *        forecast_changed | cost_variance_high | variation_submitted | cash_flow_risk
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
            'budget_exceeded' => 'Budget Exceeded',
            'invoice_overdue' => 'Invoice Overdue',
            'retention_due' => 'Retention Due for Release',
            'payment_received' => 'Payment Received',
            'forecast_changed' => 'Forecast Changed',
            'cost_variance_high' => 'Cost Variance High',
            'variation_submitted' => 'Variation Submitted',
            'cash_flow_risk' => 'Cash Flow Risk',
            default => 'Project Finance Update',
        };
    }

    protected function getBody(): string
    {
        $project = $this->data['project_name'] ?? 'a project';
        $ref = $this->data['reference'] ?? '';

        return match ($this->type) {
            'budget_exceeded' => "Budget {$ref} on {$project} has exceeded its approved amount.",
            'invoice_overdue' => "Invoice {$ref} for {$project} is overdue.",
            'retention_due' => "Retention {$ref} on {$project} is due for release.",
            'payment_received' => "A payment of {$ref} was received for {$project}.",
            'forecast_changed' => "The cost/revenue forecast for {$project} has changed significantly.",
            'cost_variance_high' => "Cost variance on {$project} has exceeded the configured threshold.",
            'variation_submitted' => "Variation {$ref} was submitted for {$project}.",
            'cash_flow_risk' => "{$project} is projected to have a negative cash flow position.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'budget_exceeded' => 'heroicon-o-exclamation-triangle',
            'invoice_overdue' => 'heroicon-o-receipt-percent',
            'retention_due' => 'heroicon-o-lock-open',
            'payment_received' => 'heroicon-o-banknotes',
            'forecast_changed' => 'heroicon-o-presentation-chart-line',
            'cost_variance_high' => 'heroicon-o-adjustments-horizontal',
            'variation_submitted' => 'heroicon-o-arrows-right-left',
            'cash_flow_risk' => 'heroicon-o-arrow-trending-down',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'budget_exceeded', 'invoice_overdue', 'cash_flow_risk' => 'danger',
            'retention_due', 'cost_variance_high', 'variation_submitted', 'forecast_changed' => 'warning',
            'payment_received' => 'success',
            default => 'info',
        };
    }
}
