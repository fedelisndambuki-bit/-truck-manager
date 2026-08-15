<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaterialsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: low_stock | out_of_stock | reorder_point | grn_received |
     *        transfer_pending | putaway_pending | expiring_item
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
            ->action('View Materials Management', url('/admin/materials'));
    }

    protected function getTitle(): string
    {
        return match ($this->type) {
            'low_stock' => 'Low Stock',
            'out_of_stock' => 'Out of Stock',
            'reorder_point' => 'Reorder Point Reached',
            'grn_received' => 'Goods Received',
            'transfer_pending' => 'Transfer Pending',
            'putaway_pending' => 'Put-Away Pending',
            'expiring_item' => 'Item Expiring Soon',
            default => 'Materials Alert',
        };
    }

    protected function getBody(): string
    {
        $ref = $this->data['reference'] ?? '';

        return match ($this->type) {
            'low_stock' => "{$ref} is below its minimum stock level.",
            'out_of_stock' => "{$ref} is out of stock.",
            'reorder_point' => "{$ref} has reached its reorder point.",
            'grn_received' => "Goods have been received for {$ref}.",
            'transfer_pending' => "Stock transfer {$ref} has been pending for too long.",
            'putaway_pending' => "Put-away task {$ref} has been pending for too long.",
            'expiring_item' => "{$ref} is expiring soon.",
            default => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'low_stock', 'reorder_point' => 'heroicon-o-arrow-trending-down',
            'out_of_stock' => 'heroicon-o-x-circle',
            'grn_received' => 'heroicon-o-clipboard-document-check',
            'transfer_pending' => 'heroicon-o-arrows-right-left',
            'putaway_pending' => 'heroicon-o-inbox-arrow-down',
            'expiring_item' => 'heroicon-o-calendar-days',
            default => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'out_of_stock', 'expiring_item' => 'danger',
            'low_stock', 'reorder_point', 'transfer_pending', 'putaway_pending' => 'warning',
            default => 'info',
        };
    }
}
