<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Validator;

class ManageSubscription extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Manage Subscription';
    protected static ?string $title           = 'Manage Subscription';
    protected static ?string $slug            = 'manage-subscription';
    protected static ?int    $navigationSort  = 99;
    protected static ?string $navigationGroup = 'Account';
    protected static string  $view            = 'filament.pages.manage-subscription';

    // ─── Billing form public properties (Livewire) ───────────────
    public string  $billing_name     = '';
    public string  $billing_email    = '';
    public string  $billing_phone    = '';
    public string  $billing_country  = 'KE';
    public string  $billing_currency = 'KES';
    public string  $tax_number       = '';
    public string  $payment_method   = 'card';
    public ?int    $selected_plan_id = null;

    public function mount(): void
    {
        $user    = auth()->user();
        $company = $user?->company;

        $this->billing_name     = $company?->name ?? '';
        $this->billing_email    = $user?->email ?? '';
        $this->billing_phone    = $company?->phone ?? '';
        $this->billing_country  = 'KE';
        $this->billing_currency = 'KES';

        // Default to current plan or the middle plan (Growth)
        $this->selected_plan_id = $company?->plan_id
            ?? Plan::orderBy('price')->skip(1)->value('id')
            ?? Plan::orderBy('price')->value('id');
    }

    // ─── View data ───────────────────────────────────────────────

    public function getViewData(): array
    {
        $user    = auth()->user();
        $company = $user?->company;

        if (! $company) {
            // Super Admin or users without a company — show plans only
            return [
                'company'         => null,
                'subscription'    => null,
                'hasActiveSub'    => false,
                'currentPlan'     => null,
                'selectedPlan'    => \App\Models\Plan::with('modules')->orderBy('price')->skip(1)->first(),
                'plans'           => \App\Models\Plan::with('modules')->orderBy('price')->get(),
                'statusBadge'     => ['label' => 'No Account', 'color' => '#64748B', 'emoji' => '⚪'],
                'trialDaysLeft'   => null,
                'trialActive'     => false,
                'trialExpired'    => false,
                'daysUsed'        => 0,
                'progressPercent' => 0,
                'nextPaymentDate' => null,
                'renewalDate'     => null,
                'payments'        => collect(),
                'timeline'        => [],
                'autoRenewal'     => false,
            ];
        }

        $sub          = $company->currentSubscription;
        $hasActiveSub = $sub && $sub->isActive();

        // ── Trial calculations ──
        $trialDaysLeft   = null;
        $trialActive     = false;
        $trialExpired    = false;
        $daysUsed        = 0;
        $progressPercent = 0;

        if (! $hasActiveSub && $company->trial_ends_at) {
            $trialDaysLeft   = (int) now()->diffInDays($company->trial_ends_at, false);
            $trialActive     = $trialDaysLeft >= 0;
            $trialExpired    = $trialDaysLeft < 0;
            $daysUsed        = max(0, 7 - $trialDaysLeft);
            $progressPercent = min(100, (int) round(($daysUsed / 7) * 100));
        }

        // ── Status badge ──
        $statusBadge = $this->resolveStatusBadge($sub, $hasActiveSub, $trialActive, $trialExpired);

        // ── Plans ──
        $plans       = Plan::with('modules')->orderBy('price')->get();
        $selectedPlan = Plan::with('modules')->find($this->selected_plan_id);
        $currentPlan  = $hasActiveSub ? $sub->plan?->loadMissing('modules') : null;

        // ── Billing dates ──
        $nextPaymentDate = $trialActive  ? $company->trial_ends_at  : null;
        $renewalDate     = $hasActiveSub ? $sub->expires_at          : null;

        // ── History ──
        $payments = $company->subscriptionPayments()->orderByDesc('created_at')->limit(20)->get();

        return [
            'company'         => $company,
            'subscription'    => $sub,
            'hasActiveSub'    => $hasActiveSub,
            'currentPlan'     => $currentPlan,
            'selectedPlan'    => $selectedPlan,
            'plans'           => $plans,
            'statusBadge'     => $statusBadge,
            'trialDaysLeft'   => $trialDaysLeft,
            'trialActive'     => $trialActive,
            'trialExpired'    => $trialExpired,
            'daysUsed'        => $daysUsed,
            'progressPercent' => $progressPercent,
            'nextPaymentDate' => $nextPaymentDate,
            'renewalDate'     => $renewalDate,
            'payments'        => $payments,
            'timeline'        => $this->buildTimeline($company, $sub, $hasActiveSub, $trialActive, $trialExpired),
            'autoRenewal'     => (bool) ($sub?->auto_renewal ?? true),
        ];
    }

    // ─── Livewire actions ─────────────────────────────────────────

    public function initiatePayment(): void
    {
        $validator = Validator::make([
            'billing_name'     => $this->billing_name,
            'billing_email'    => $this->billing_email,
            'billing_phone'    => $this->billing_phone,
            'selected_plan_id' => $this->selected_plan_id,
        ], [
            'billing_name'     => 'required|string|min:2',
            'billing_email'    => 'required|email',
            'billing_phone'    => 'required|string|min:9',
            'selected_plan_id' => 'required|exists:plans,id',
        ]);

        if ($validator->fails()) {
            Notification::make()
                ->title('Please fill in all required billing fields.')
                ->danger()
                ->send();
            return;
        }

        $company = auth()->user()?->company;
        $plan    = Plan::findOrFail($this->selected_plan_id);

        try {
            $url = app(SubscriptionService::class)->initiatePayment($company, $plan, [
                'billing_name'     => $this->billing_name,
                'billing_email'    => $this->billing_email,
                'billing_phone'    => $this->billing_phone,
                'billing_country'  => $this->billing_country,
                'billing_currency' => $this->billing_currency,
                'tax_number'       => $this->tax_number ?: null,
                'payment_method'   => $this->payment_method,
            ]);

            $this->redirect($url);

        } catch (\Exception $e) {
            Notification::make()
                ->title('Payment initialization failed.')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function toggleAutoRenewal(): void
    {
        $company  = auth()->user()?->company;
        $newValue = app(SubscriptionService::class)->toggleAutoRenewal($company);

        Notification::make()
            ->title('Auto-renewal ' . ($newValue ? 'enabled ✅' : 'disabled ⚫'))
            ->success()
            ->send();
    }

    // ─── Helpers ─────────────────────────────────────────────────

    protected function resolveStatusBadge($sub, bool $hasActiveSub, bool $trialActive, bool $trialExpired): array
    {
        if ($hasActiveSub) {
            $days = $sub->daysUntilExpiry();
            if ($days <= 7)  return ['label' => 'Renewal Due', 'color' => '#f97316', 'emoji' => '🟠'];
            return             ['label' => 'Active',       'color' => '#16a34a', 'emoji' => '🟢'];
        }
        if ($trialActive)  return ['label' => 'Trial',          'color' => '#2563eb', 'emoji' => '🔵'];
        if ($trialExpired) return ['label' => 'Expired',        'color' => '#dc2626', 'emoji' => '🔴'];
        return                    ['label' => 'Pending Payment', 'color' => '#ca8a04', 'emoji' => '🟡'];
    }

    protected function buildTimeline(Company $company, $sub, bool $hasActiveSub, bool $trialActive, bool $trialExpired): array
    {
        return [
            [
                'label'  => 'Registration',
                'date'   => $company->created_at?->format('d M Y'),
                'done'   => true,
                'active' => false,
            ],
            [
                'label'  => 'Trial Started',
                'date'   => $company->created_at?->format('d M Y'),
                'done'   => true,
                'active' => false,
            ],
            [
                'label'   => 'Trial Ends',
                'date'    => $company->trial_ends_at?->format('d M Y'),
                'done'    => $trialExpired || $hasActiveSub,
                'active'  => $trialActive,
                'pending' => false,
            ],
            [
                'label'   => 'Subscription Payment',
                'date'    => $hasActiveSub ? $sub?->started_at?->format('d M Y') : null,
                'done'    => $hasActiveSub,
                'active'  => false,
                'pending' => ! $hasActiveSub,
            ],
            [
                'label'   => 'Active Subscription',
                'date'    => $hasActiveSub ? ('Until ' . $sub?->expires_at?->format('d M Y')) : null,
                'done'    => $hasActiveSub,
                'active'  => $hasActiveSub,
                'pending' => ! $hasActiveSub,
            ],
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }
}
