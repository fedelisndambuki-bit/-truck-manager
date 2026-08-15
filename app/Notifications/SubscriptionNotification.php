<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Types: trial_started | trial_reminder | trial_expired |
     *        payment_successful | payment_failed | subscription_expired |
     *        subscription_renewed | renewal_due | renewal_failed |
     *        subscription_cancelled
     */
    public function __construct(
        protected string $type,
        protected array $data = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    // ─────────────────────────────────────────────────────────────
    // Database channel
    // ─────────────────────────────────────────────────────────────

    public function toDatabase(object $notifiable): array
    {
        return array_merge($this->data, [
            'type'  => $this->type,
            'icon'  => $this->getIcon(),
            'title' => $this->getTitle(),
            'body'  => $this->getBody(),
            'color' => $this->getColor(),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    // ─────────────────────────────────────────────────────────────
    // Mail channel
    // ─────────────────────────────────────────────────────────────

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->getEmailSubject())
            ->greeting('Hello ' . $notifiable->name . ',');

        foreach ($this->getEmailLines() as $line) {
            $mail->line($line);
        }

        if ($url = $this->getActionUrl()) {
            $mail->action($this->getActionText(), $url);
        }

        return $mail->line('Thank you for using Tanaki Hub!');
    }

    // ─────────────────────────────────────────────────────────────
    // Content helpers
    // ─────────────────────────────────────────────────────────────

    protected function getTitle(): string
    {
        $days = $this->data['days_left'] ?? '?';

        return match ($this->type) {
            'trial_started'        => '🎉 Your 7-Day Free Trial Has Started!',
            'trial_reminder'       => "⏳ Trial Expiring in {$days} Days",
            'trial_expired'        => '🔴 Your Free Trial Has Expired',
            'payment_successful'   => '✅ Payment Successful — Subscription Activated',
            'payment_failed'       => '❌ Payment Failed',
            'subscription_expired' => '🔴 Your Subscription Has Expired',
            'subscription_renewed' => '✅ Subscription Renewed Successfully',
            'renewal_due'          => "🔔 Subscription Renewal Due in {$days} Days",
            'renewal_failed'       => '⚠️ Subscription Renewal Payment Failed',
            'subscription_cancelled' => '⚫ Auto-Renewal Disabled',
            default                => 'Subscription Update',
        };
    }

    protected function getBody(): string
    {
        $days = $this->data['days_left'] ?? '?';
        $plan = $this->data['plan'] ?? 'your plan';

        return match ($this->type) {
            'trial_started'        => 'Welcome! Your 7-day free trial is now active.',
            'trial_reminder'       => "Your trial ends in {$days} days. Subscribe to keep full access.",
            'trial_expired'        => 'Your free trial has ended. Subscribe to restore access.',
            'payment_successful'   => "Your {$plan} subscription is now active.",
            'payment_failed'       => 'Your payment could not be processed. Please try again.',
            'subscription_expired' => 'Your paid subscription has expired. Renew to restore access.',
            'subscription_renewed' => 'Your subscription has been renewed for another month.',
            'renewal_due'          => "Your subscription expires in {$days} days.",
            'renewal_failed'       => "We couldn't charge your card to renew {$plan}. Please update your payment method.",
            'subscription_cancelled' => 'Auto-renewal has been disabled.',
            default                => '',
        };
    }

    protected function getIcon(): string
    {
        return match ($this->type) {
            'trial_started'        => 'heroicon-o-rocket-launch',
            'trial_reminder'       => 'heroicon-o-clock',
            'trial_expired'        => 'heroicon-o-x-circle',
            'payment_successful'   => 'heroicon-o-check-circle',
            'payment_failed'       => 'heroicon-o-exclamation-circle',
            'subscription_expired' => 'heroicon-o-x-circle',
            'subscription_renewed' => 'heroicon-o-arrow-path',
            'renewal_due'          => 'heroicon-o-bell',
            'renewal_failed'       => 'heroicon-o-exclamation-triangle',
            'subscription_cancelled' => 'heroicon-o-x-mark',
            default                => 'heroicon-o-bell',
        };
    }

    protected function getColor(): string
    {
        return match ($this->type) {
            'trial_started'        => 'success',
            'trial_reminder'       => 'warning',
            'trial_expired'        => 'danger',
            'payment_successful'   => 'success',
            'payment_failed'       => 'danger',
            'subscription_expired' => 'danger',
            'subscription_renewed' => 'success',
            'renewal_due'          => 'warning',
            'renewal_failed'       => 'danger',
            'subscription_cancelled' => 'gray',
            default                => 'info',
        };
    }

    protected function getEmailSubject(): string
    {
        $days = $this->data['days_left'] ?? '?';

        return match ($this->type) {
            'trial_started'        => '🎉 Welcome to Tanaki Hub! Your Free Trial Has Started',
            'trial_reminder'       => "⏳ Your Trial Ends in {$days} Days — Subscribe Now",
            'trial_expired'        => '🔴 Your Free Trial Has Expired — Subscribe to Continue',
            'payment_successful'   => '✅ Payment Confirmed — Your Subscription Is Active',
            'payment_failed'       => '❌ Payment Failed — Please Retry',
            'subscription_expired' => '🔴 Your Subscription Has Expired — Renew to Continue',
            'subscription_renewed' => '✅ Subscription Renewed — You\'re All Set for Another Month',
            'renewal_failed'       => '⚠️ We Couldn\'t Renew Your Subscription — Action Needed',
            'renewal_due'          => "🔔 Subscription Renewal Due in {$days} Days",
            'subscription_cancelled' => 'Auto-Renewal Disabled — Action Required',
            default                => 'Tanaki Hub Subscription Update',
        };
    }

    protected function getEmailLines(): array
    {
        $days   = $this->data['days_left'] ?? '?';
        $plan   = $this->data['plan'] ?? 'your plan';
        $amount = 'KES ' . number_format((float) ($this->data['amount'] ?? 0));
        $expiry = $this->data['expiry'] ?? $this->data['renewal_date'] ?? '';

        return match ($this->type) {
            'trial_started' => [
                'Your **7-day free trial** of Tanaki Hub has started. You have full access to all features.',
                'To ensure uninterrupted service, consider subscribing before your trial ends.',
            ],
            'trial_reminder' => [
                "Your free trial expires in **{$days} days**.",
                'After expiry you will need an active subscription to continue using Tanaki Hub.',
                'Subscribe now to avoid interruption.',
            ],
            'trial_expired' => [
                'Your 7-day free trial of Tanaki Hub has ended.',
                'Please subscribe to one of our plans to restore full access.',
                'Your data is safe and will be available once you re-subscribe.',
            ],
            'payment_successful' => [
                "Your payment of **{$amount}** has been confirmed.",
                "Your **{$plan}** subscription is now active.",
                "Subscription valid until: **{$expiry}**.",
            ],
            'payment_failed' => [
                'Unfortunately, your payment could not be processed.',
                'Please visit your subscription page and retry with a valid payment method.',
                'If the issue persists, contact our support team.',
            ],
            'subscription_expired' => [
                "Your **{$plan}** subscription has expired.",
                'Please renew to restore full access to Tanaki Hub.',
                'Your data is safe and will be available again once you renew.',
            ],
            'renewal_failed' => [
                "We tried to renew your **{$plan}** subscription but the charge to your saved card didn't go through.",
                'We\'ll keep retrying for a few days — please update your payment method to avoid any interruption.',
                'Your access continues uninterrupted during this grace period.',
            ],
            'subscription_renewed' => [
                'Your Tanaki Hub subscription has been renewed.',
                "Your account is active until: **{$expiry}**.",
            ],
            'renewal_due' => [
                "Your subscription expires in **{$days} days** on {$expiry}.",
                'If auto-renewal is enabled your subscription will renew automatically.',
                'Otherwise, please visit your billing page to renew manually.',
            ],
            'subscription_cancelled' => [
                'You have disabled auto-renewal for your Tanaki Hub subscription.',
                'Your subscription will remain active until its expiry date.',
                'You will need to renew manually to continue after expiry.',
            ],
            default => ['Your subscription status has been updated.'],
        };
    }

    protected function getActionUrl(): ?string
    {
        return match ($this->type) {
            'trial_reminder', 'trial_expired', 'payment_failed', 'renewal_due', 'subscription_expired', 'renewal_failed' =>
                url('/admin/manage-subscription'),
            'payment_successful', 'subscription_renewed', 'subscription_cancelled' =>
                url('/admin/manage-subscription'),
            default => null,
        };
    }

    protected function getActionText(): string
    {
        return match ($this->type) {
            'trial_reminder', 'trial_expired' => 'Subscribe Now',
            'payment_failed'                  => 'Retry Payment',
            'renewal_due'                     => 'Manage Subscription',
            'subscription_expired'            => 'Renew Now',
            'renewal_failed'                  => 'Update Payment Method',
            default                           => 'View Subscription',
        };
    }
}
