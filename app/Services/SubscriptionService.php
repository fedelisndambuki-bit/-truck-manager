<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Notifications\SubscriptionNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function __construct(protected PaystackService $paystack) {}

    // ─────────────────────────────────────────────────────────────
    // Payment Initiation
    // ─────────────────────────────────────────────────────────────

    public function initiatePayment(Company $company, Plan $plan, array $billing): string
    {
        $txRef = 'SUB_' . $company->id . '_' . time();

        SubscriptionPayment::create([
            'company_id'            => $company->id,
            'amount'                => $plan->price,
            'method'                => $billing['payment_method'] ?? 'card',
            'transaction_reference' => $txRef,
            'paystack_reference'    => $txRef,
            'status'                => 'pending',
            'plan_id'               => $plan->id,
            'billing_name'          => $billing['billing_name'],
            'billing_email'         => $billing['billing_email'],
            'billing_phone'         => $billing['billing_phone'] ?? null,
            'billing_country'       => $billing['billing_country'] ?? 'KE',
            'billing_currency'      => $billing['billing_currency'] ?? 'KES',
            'tax_number'            => $billing['tax_number'] ?? null,
            'promo_code'            => $billing['promo_code'] ?? null,
        ]);

        // Map payment method to Paystack channels
        $channels = match ($billing['payment_method'] ?? 'card') {
            'mpesa' => ['mobile_money'],
            'bank'  => ['bank_transfer'],
            default => ['card'],
        };

        return $this->paystack->initializePayment([
            'email'        => $billing['billing_email'],
            'amount'       => $plan->price,
            'reference'    => $txRef,
            'callback_url' => route('subscription.callback'),
            'currency'     => $billing['billing_currency'] ?? 'KES',
            'channels'     => $channels,
            'metadata'     => [
                'payment_type' => 'subscription',
                'company_id'   => $company->id,
                'plan_id'      => $plan->id,
                'billing_name' => $billing['billing_name'],
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Payment Callback Handling
    // ─────────────────────────────────────────────────────────────

    public function handleCallback(string $reference): bool
    {
        $payment = SubscriptionPayment::where('paystack_reference', $reference)
            ->orWhere('transaction_reference', $reference)
            ->first();

        if (! $payment || $payment->status === 'paid') {
            return $payment?->status === 'paid'; // idempotent
        }

        try {
            $verified = $this->paystack->verifyTransaction($reference);

            if (($verified['status'] ?? '') !== 'success') {
                $payment->update(['status' => 'failed']);
                $this->notify($payment->company, 'payment_failed', ['amount' => $payment->amount]);
                return false;
            }

            DB::transaction(fn () => $this->activateSubscription(
                $payment,
                $reference,
                (string) ($verified['id'] ?? ''),
                $verified['authorization'] ?? [],
            ));
            return true;

        } catch (\Exception $e) {
            Log::error('Subscription callback error', [
                'reference' => $reference,
                'error'     => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Subscription Activation
    // ─────────────────────────────────────────────────────────────

    protected function activateSubscription(SubscriptionPayment $payment, string $reference, string $paystackTxId = '', array $authorization = []): void
    {
        $plan    = Plan::with('modules')->findOrFail($payment->plan_id);
        $company = $payment->company;

        // Expire any existing active subscriptions
        Subscription::where('company_id', $company->id)
            ->where('status', 'active')
            ->update(['status' => 'expired']);

        $subscription = Subscription::create([
            'company_id'     => $company->id,
            'plan_id'        => $plan->id,
            'plan_name'      => $plan->name,
            'price'          => $plan->price,
            'truck_limit'    => $plan->truck_limit,
            'driver_limit'   => $plan->driver_limit,
            'ai_limit'       => $plan->ai_requests_limit,
            'billing_cycle'  => 'monthly',
            'status'         => 'active',
            'started_at'     => now(),
            'expires_at'     => now()->addMonth(),
            'auto_renewal'   => true,
            'reminders_sent' => [],
        ]);

        $payment->update([
            'subscription_id'         => $subscription->id,
            'status'                  => 'paid',
            'paystack_reference'      => $reference,
            'paystack_transaction_id' => $paystackTxId,
            'paid_at'                 => now(),
        ]);

        $companyUpdate = [
            'subscription_id' => $subscription->id,
            'plan_id'         => $plan->id,
            'status'          => 'active',
        ];

        // Only a card payment comes back "reusable" — save it so
        // processRenewals() can auto-charge future renewals without the
        // customer re-entering their card. Mobile money/bank transfer
        // authorizations aren't reusable, so this stays empty for them and
        // they fall back to manual renewal via the reminder emails.
        if (! empty($authorization['authorization_code'])) {
            $companyUpdate['paystack_authorization_code']     = $authorization['authorization_code'];
            $companyUpdate['paystack_authorization_reusable'] = (bool) ($authorization['reusable'] ?? false);
            $companyUpdate['paystack_card_last4']             = $authorization['last4'] ?? null;
            $companyUpdate['paystack_card_type']              = $authorization['card_type'] ?? null;
        }

        $company->update($companyUpdate);

        $this->notify($company, 'payment_successful', [
            'plan'   => $plan->name,
            'amount' => $plan->price,
            'expiry' => $subscription->expires_at->format('d M Y'),
        ]);
    }

    /**
     * Activate a subscription using data from a verified Paystack webhook.
     * No API re-verification needed — the webhook signature already proves authenticity.
     */
    public function activateFromWebhookData(string $reference, array $webhookData): void
    {
        $payment = SubscriptionPayment::where('paystack_reference', $reference)
            ->orWhere('transaction_reference', $reference)
            ->first();

        if (! $payment || $payment->status === 'paid') {
            return; // idempotent
        }

        $paystackTxId = (string) ($webhookData['id'] ?? '');

        DB::transaction(fn () => $this->activateSubscription(
            $payment,
            $reference,
            $paystackTxId,
            $webhookData['authorization'] ?? [],
        ));
    }

    // ─────────────────────────────────────────────────────────────
    // Control Room manual management
    //
    // The real Paystack flow (activateSubscription() above) is the only
    // path that used to update companies.plan_id/subscription_id/status.
    // Creating or editing a Subscription/SubscriptionPayment by hand in the
    // Control Room previously just saved the row with no effect on what the
    // tenant could actually do — these two methods give manual admin
    // actions the same real effect as a real payment.
    // ─────────────────────────────────────────────────────────────

    /**
     * Make $subscription the company's current subscription if its status
     * says it should be, or reflect the company's status if it's being
     * expired/canceled while it was already the current one.
     */
    public function syncCompanyFromSubscription(Subscription $subscription): void
    {
        $company = $subscription->company;

        if (! $company) {
            return;
        }

        if ($subscription->status === 'active') {
            // Only one subscription should be "active" per company at a time.
            Subscription::where('company_id', $company->id)
                ->where('id', '!=', $subscription->id)
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            $company->update([
                'subscription_id' => $subscription->id,
                'plan_id'         => $subscription->plan_id,
                'status'          => 'active',
            ]);

            return;
        }

        if ((int) $company->subscription_id === (int) $subscription->id
            && in_array($subscription->status, ['expired', 'canceled'], true)) {
            $company->update(['status' => $subscription->status]);
        }
    }

    /**
     * Record a manually-confirmed payment (e.g. a bank transfer or M-Pesa
     * paybill payment an admin reconciles by hand) against its linked
     * subscription: activates the subscription and extends its expiry,
     * then propagates that to the company exactly like a real gateway
     * payment would.
     */
    public function confirmManualPayment(SubscriptionPayment $payment): void
    {
        if ($payment->status !== 'paid' || ! $payment->subscription_id) {
            return;
        }

        $subscription = $payment->subscription;

        if (! $subscription) {
            return;
        }

        if (! $payment->paid_at) {
            $payment->update(['paid_at' => now()]);
        }

        $extendFrom = $subscription->expires_at?->isFuture() ? $subscription->expires_at : now();

        $subscription->update([
            'status'     => 'active',
            'started_at' => $subscription->started_at ?? now(),
            'expires_at' => $extendFrom->copy()->addMonth(),
        ]);

        $this->syncCompanyFromSubscription($subscription);
    }

    // ─────────────────────────────────────────────────────────────
    // Recurring Renewal Charging (called by scheduled command)
    //
    // A subscription that reaches expiry with auto_renewal on gets a
    // RENEWAL_GRACE_DAYS-day window where this is retried daily against the
    // company's saved card. If it never succeeds in that window (or there's
    // no reusable card to charge at all — mobile money/bank transfer
    // payments aren't reusable), sendDailyReminders() expires it once the
    // window closes.
    // ─────────────────────────────────────────────────────────────

    public const RENEWAL_GRACE_DAYS = 3;

    public function processRenewals(): void
    {
        Subscription::where('status', 'active')
            ->where('auto_renewal', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('expires_at', '>', now()->subDays(self::RENEWAL_GRACE_DAYS))
            ->each(function (Subscription $subscription) {
                $this->attemptRenewalCharge($subscription);
            });
    }

    protected function attemptRenewalCharge(Subscription $subscription): void
    {
        $company = $subscription->company;

        if (! $company || ! $company->paystack_authorization_code || ! $company->paystack_authorization_reusable) {
            // Nothing chargeable on file — leave it for the reminder emails
            // and eventual grace-period expiry to handle.
            return;
        }

        $plan = $subscription->plan ?? Plan::find($subscription->plan_id);

        if (! $plan) {
            return;
        }

        $admin = $company->users()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Company Admin'))
            ->first();

        if (! $admin) {
            return;
        }

        // One attempt per day: skip if we already tried today.
        $attemptKey = 'renewal_charge_attempt_' . now()->toDateString();
        if ($subscription->reminderWasSent($attemptKey)) {
            return;
        }
        $subscription->markReminderSent($attemptKey);

        $reference = 'RENEW_' . $company->id . '_' . time();

        $payment = SubscriptionPayment::create([
            'company_id'            => $company->id,
            'subscription_id'       => $subscription->id,
            'plan_id'               => $plan->id,
            'amount'                => $plan->price,
            'method'                => 'card',
            'transaction_reference' => $reference,
            'paystack_reference'    => $reference,
            'status'                => 'pending',
            'billing_email'         => $admin->email,
        ]);

        try {
            $result = $this->paystack->chargeAuthorization([
                'authorization_code' => $company->paystack_authorization_code,
                'email'              => $admin->email,
                'amount'             => $plan->price,
                'reference'          => $reference,
            ]);

            if (($result['status'] ?? '') === 'success') {
                $payment->update([
                    'status'                  => 'paid',
                    'paystack_transaction_id' => (string) ($result['id'] ?? ''),
                    'paid_at'                 => now(),
                ]);

                $subscription->update([
                    'status'     => 'active',
                    'expires_at' => $subscription->expires_at->copy()->addMonth(),
                ]);

                $this->notify($company, 'subscription_renewed', [
                    'plan'   => $plan->name,
                    'expiry' => $subscription->expires_at->format('d M Y'),
                ]);

                return;
            }

            $payment->update(['status' => 'failed']);
            $this->notify($company, 'renewal_failed', ['plan' => $plan->name]);

        } catch (\Exception $e) {
            $payment->update(['status' => 'failed']);

            Log::error('Subscription renewal charge error', [
                'company_id' => $company->id,
                'error'      => $e->getMessage(),
            ]);

            $this->notify($company, 'renewal_failed', ['plan' => $plan->name]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Auto-Renewal Toggle
    // ─────────────────────────────────────────────────────────────

    public function toggleAutoRenewal(Company $company): bool
    {
        $sub = $company->currentSubscription;
        if (! $sub) return false;

        $newValue = ! ($sub->auto_renewal ?? true);
        $sub->update(['auto_renewal' => $newValue]);

        if (! $newValue) {
            $this->notify($company, 'subscription_cancelled');
        }

        return $newValue;
    }

    // ─────────────────────────────────────────────────────────────
    // Daily Reminder Dispatch (called by scheduled command)
    // ─────────────────────────────────────────────────────────────

    public function sendDailyReminders(): void
    {
        // 1. Trial reminders
        Company::where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->each(function (Company $company) {
                $sub = $company->currentSubscription;
                if ($sub && $sub->isActive()) return; // skip if already subscribed

                $daysLeft = (int) now()->diffInDays($company->trial_ends_at, false);

                if ($daysLeft === 3) {
                    $this->sendOnce($company, 'trial_3days', 'trial_reminder', ['days_left' => 3]);
                } elseif ($daysLeft === 1) {
                    $this->sendOnce($company, 'trial_1day', 'trial_reminder', ['days_left' => 1]);
                } elseif ($daysLeft === 0) {
                    $this->sendOnce($company, 'trial_expired', 'trial_expired');
                }
            });

        // 2. Renewal reminders
        Subscription::where('status', 'active')
            ->whereNotNull('expires_at')
            ->each(function (Subscription $sub) {
                $daysLeft = (int) now()->diffInDays($sub->expires_at, false);
                $company  = $sub->company;

                if ($daysLeft === 7 && ! $sub->reminderWasSent('renewal_7days')) {
                    $this->sendOnce($company, 'renewal_7days', 'renewal_due', [
                        'days_left'    => 7,
                        'renewal_date' => $sub->expires_at->format('d M Y'),
                    ]);
                    $sub->markReminderSent('renewal_7days');
                } elseif ($daysLeft === 1 && ! $sub->reminderWasSent('renewal_1day')) {
                    $this->sendOnce($company, 'renewal_1day', 'renewal_due', [
                        'days_left'    => 1,
                        'renewal_date' => $sub->expires_at->format('d M Y'),
                    ]);
                    $sub->markReminderSent('renewal_1day');
                } elseif ($daysLeft < 0 && (! $sub->auto_renewal || $daysLeft < -self::RENEWAL_GRACE_DAYS)) {
                    // Either auto-renewal is off (expire immediately), or
                    // auto-renewal is on but processRenewals() never managed
                    // a successful charge within the grace window — expire.
                    $sub->update(['status' => 'expired']);
                    $company->update(['status' => 'expired']);
                    $this->notify($company, 'subscription_expired', ['plan' => $sub->plan_name]);
                }
                // else: within the auto-renewal grace window — leave it to
                // processRenewals(), which runs before this and retries daily.
            });
    }

    // ─────────────────────────────────────────────────────────────
    // Notification Helpers
    // ─────────────────────────────────────────────────────────────

    public function notify(Company $company, string $type, array $data = []): void
    {
        $admin = $company->users()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Company Admin'))
            ->first();

        if ($admin) {
            $admin->notify(new SubscriptionNotification($type, array_merge(
                ['company_name' => $company->name],
                $data
            )));
        }
    }

    protected function sendOnce(Company $company, string $key, string $type, array $data = []): void
    {
        $admin = $company->users()
            ->whereHas('roles', fn ($q) => $q->where('name', 'Company Admin'))
            ->first();

        if (! $admin) return;

        // Dedup: skip if the same key was already notified in the last 23 hours
        $alreadySent = $admin->notifications()
            ->where('data', 'like', '%"notification_key":"' . $key . '"%')
            ->where('created_at', '>=', now()->subHours(23))
            ->exists();

        if (! $alreadySent) {
            $admin->notify(new SubscriptionNotification($type, array_merge(
                ['company_name' => $company->name, 'notification_key' => $key],
                $data
            )));
        }
    }
}
