<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockpileNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: stock_below_minimum | stockpile_full | reservation_expiring |
     *        large_adjustment_requested | quality_approval_pending |
     *        inventory_variance_detected | transfer_completed | physical_count_due
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
            ->action('View Stockpile Management', url('/admin/stockpile-management'));
    }

    protected function getTitle(): string
    {
        return match ($this->type) {
            'stock_below_minimum' => 'Stock Below Minimum',
            'stockpile_full' => 'Stockpile Full',
            'reservation_expiring' => 'Reservation Expiring',
            'large_adjustment_requested' => 'Large Adjustment Requested',
            'quality_approval_pending' => 'Quality Approval Pending',
            'inventory_variance_detected' => 'Inventory Variance Detected',
            'transfer_completed' => 'Transfer Completed',
            'physical_count_due' => 'Physical Count Due',
            default => 'Stockpile Management Alert',
        };
    }

    protected function getBody(): string
    {
        $ref = $this->data['reference'] ?? '';

        return match ($this->type) {
            'stock_below_minimum' => "Stockpile {$ref} has fallen below its minimum level.",
            'stockpile_full' => "Stockpile {$ref} has reached maximum capacity.",
            'reservation_expiring' => "Reservation {$ref} is expiring soon.",
            'large_adjustment_requested' => "A large stock adjustment was requested for {$ref}.",
            'quality_approval_pending' => "Stockpile {$ref} is awaiting quality approval before it can be dispatched.",
            'inventory_variance_detected' => "A significant inventory variance was detected for {$ref}.",
            'transfer_completed' => "Stock transfer {$ref} has been completed.",
            'physical_count_due' => "A physical stock count is due for {$ref}.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'stock_below_minimum' => 'heroicon-o-arrow-trending-down',
            'stockpile_full' => 'heroicon-o-archive-box',
            'reservation_expiring' => 'heroicon-o-clock',
            'large_adjustment_requested' => 'heroicon-o-adjustments-horizontal',
            'quality_approval_pending' => 'heroicon-o-beaker',
            'inventory_variance_detected' => 'heroicon-o-exclamation-triangle',
            'transfer_completed' => 'heroicon-o-check-circle',
            'physical_count_due' => 'heroicon-o-clipboard-document-check',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'transfer_completed' => 'success',
            'stock_below_minimum', 'stockpile_full', 'reservation_expiring', 'large_adjustment_requested', 'physical_count_due' => 'warning',
            'inventory_variance_detected' => 'danger',
            'quality_approval_pending' => 'warning',
            default => 'info',
        };
    }
}
