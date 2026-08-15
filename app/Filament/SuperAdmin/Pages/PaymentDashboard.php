<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\LedgerEntry;
use App\Models\SubscriptionPayment;
use App\Models\WalletFunding;
use App\Modules\Settlement\Settlement; // payment_settlements table — retained for settlement batches
use App\Modules\Payments\Transaction;  // transactions table — retained for STK push logging
use App\Services\FinancialService;
use App\Services\MpesaService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentDashboard extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Billing';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $navigationLabel = 'Payment Dashboard';
    protected static string  $view            = 'filament.super-admin.pages.payment-dashboard';
    protected static ?string $title           = 'Payment Dashboard';

    // Human-readable labels for fin_ledger_entries account_code values
    private const ACCOUNT_LABELS = [
        FinancialService::ACC_BANK_GATEWAY         => 'BANK_GATEWAY',
        FinancialService::ACC_ACCOUNTS_RECEIVABLE  => 'ACCOUNTS_REC',
        FinancialService::ACC_REVENUE_WALLETS      => 'REVENUE_WALLET',
        FinancialService::ACC_CASH_ON_HAND         => 'CASH',
        FinancialService::ACC_ACCOUNTS_PAYABLE     => 'ACCTS_PAYABLE',
        FinancialService::ACC_DRIVER_LIABILITY     => 'DRIVER_LIAB',
        FinancialService::ACC_GATEWAY_FEES_PAYABLE => 'GATEWAY_FEES_PAY',
        FinancialService::ACC_TRIP_REVENUE         => 'TRIP_REVENUE',
        FinancialService::ACC_CONVENIENCE_FEES     => 'CONV_FEES',
        FinancialService::ACC_EXTERNAL_FUNDING     => 'EXT_FUNDING',
        FinancialService::ACC_FUEL_EXPENSE         => 'FUEL_EXP',
        FinancialService::ACC_MAINTENANCE_EXPENSE  => 'MAINT_EXP',
        FinancialService::ACC_OPERATIONAL_EXPENSES => 'OPS_EXP',
        FinancialService::ACC_GATEWAY_FEES_EXPENSE => 'GATEWAY_FEES',
        FinancialService::ACC_FLUTTERWAVE_CLEARING => 'CLEARING',
    ];

    public function getHeader(): ?\Illuminate\Contracts\View\View
    {
        return null;
    }

    public function getViewData(): array
    {
        $thisMonth = Carbon::now()->startOfMonth();

        // ── Transactions ────────────────────────────────────────────────────────
        // Unify subscription payments + wallet fundings into a single feed.
        // SubscriptionPayment amounts are in major units (KES decimal).
        // WalletFunding amounts are in minor units (kobo ×100).

        $subscriptionTxns = SubscriptionPayment::with('company')
            ->latest()->limit(30)->get()
            ->map(fn ($p) => (object) [
                'transaction_id'    => $p->transaction_reference ?? ('SUB-' . Str::upper(Str::random(6))),
                'amount'            => (float) $p->amount,
                'payment_method'    => $p->method ?? 'paystack',
                'gateway_reference' => $p->paystack_reference ?? $p->flw_transaction_id ?? '—',
                'status'            => $p->status === 'paid' ? 'completed' : $p->status,
                'created_at'        => $p->created_at,
            ]);

        $walletTxns = WalletFunding::latest()->limit(20)->get()
            ->map(fn ($f) => (object) [
                'transaction_id'    => $f->paystack_reference ?? ('FUND-' . Str::upper(Str::random(6))),
                'amount'            => round($f->amount / 100, 2),
                'payment_method'    => $f->payment_method ?? 'paystack',
                'gateway_reference' => $f->paystack_reference ?? $f->flutterwave_tx_ref ?? '—',
                'status'            => $f->status === 'successful' ? 'completed' : $f->status,
                'created_at'        => $f->created_at,
            ]);

        $allTransactions = $subscriptionTxns->concat($walletTxns)->sortByDesc('created_at');

        // ── Settlements ─────────────────────────────────────────────────────────
        // payment_settlements table — batched platform reconciliation records.
        $settlements = Settlement::latest()->limit(20)->get();

        // ── Ledger ───────────────────────────────────────────────────────────────
        // Real double-entry ledger from fin_ledger_entries.
        // Adds account_type label for the blade template.
        $labels      = self::ACCOUNT_LABELS;
        $ledgerEntries = LedgerEntry::latest()->limit(50)->get()
            ->map(function ($entry) use ($labels) {
                $entry->account_type = $labels[$entry->account_code] ?? 'ACC-' . $entry->account_code;
                return $entry;
            });

        // ── Revenue metrics (month-to-date) ──────────────────────────────────────
        // Subscriptions are in major units; wallet fundings are in minor (÷100).
        $subGross   = (float) SubscriptionPayment::where('status', 'paid')
            ->where('created_at', '>=', $thisMonth)->sum('amount');

        $fundGross  = (float) WalletFunding::where('status', 'successful')
            ->where('created_at', '>=', $thisMonth)->sum('amount') / 100;

        $combinedGross = $subGross + $fundGross;

        // Gateway fees — fin_ledger_entries, stored in minor units (÷100)
        $gatewayFees = (float) LedgerEntry::where('account_code', FinancialService::ACC_GATEWAY_FEES_EXPENSE)
            ->where('created_at', '>=', $thisMonth)->sum('debit') / 100;

        // Platform revenue — convenience fees + trip revenue credits (minor units ÷100)
        $platformRevenue = ((float) LedgerEntry::whereIn('account_code', [
                FinancialService::ACC_TRIP_REVENUE,
                FinancialService::ACC_CONVENIENCE_FEES,
            ])->where('created_at', '>=', $thisMonth)->sum('credit')) / 100;

        // ── Daily volume (14 days) from subscription payments ─────────────────
        $dailyVolume = SubscriptionPayment::where('status', 'paid')
            ->where('created_at', '>=', Carbon::now()->subDays(14))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date')
            ->toArray();

        // ── Account balances from ledger ──────────────────────────────────────
        $cashBalance = ((float) LedgerEntry::where('account_code', FinancialService::ACC_CASH_ON_HAND)->sum('debit')
            - (float) LedgerEntry::where('account_code', FinancialService::ACC_CASH_ON_HAND)->sum('credit')) / 100;

        $liabilityBalance = ((float) LedgerEntry::where('account_code', FinancialService::ACC_ACCOUNTS_PAYABLE)->sum('credit')
            - (float) LedgerEntry::where('account_code', FinancialService::ACC_ACCOUNTS_PAYABLE)->sum('debit')) / 100;

        return [
            'transactions' => $allTransactions,
            'settlements'  => [
                'items' => $settlements,
                'stats' => [
                    'gross' => number_format($settlements->sum('gross_amount'), 2),
                    'fees'  => number_format($settlements->sum('fees_total'), 2),
                    'net'   => number_format($settlements->sum('net_amount'), 2),
                ],
            ],
            'ledger'  => $ledgerEntries,
            'reports' => [
                'gross'       => number_format($combinedGross, 2),
                'gateway'     => number_format($gatewayFees, 2),
                'platform'    => number_format($platformRevenue, 2),
                'net'         => number_format($combinedGross - $gatewayFees, 2),
                'dailyVolume' => $dailyVolume,
                'balances'    => [
                    'cash'      => number_format($cashBalance, 2),
                    'platform'  => number_format($platformRevenue, 2),
                    'gateway'   => number_format($gatewayFees, 2),
                    'liability' => number_format($liabilityBalance, 2),
                ],
            ],
            'stats' => $this->getDashboardStats(),
        ];
    }

    private function getDashboardStats(): array
    {
        $today = Carbon::today();

        $subTotal     = SubscriptionPayment::count();
        $subCompleted = SubscriptionPayment::where('status', 'paid')->count();
        $subPending   = SubscriptionPayment::where('status', 'pending')->count();
        $subFailed    = SubscriptionPayment::where('status', 'failed')->count();

        $fundTotal     = WalletFunding::count();
        $fundCompleted = WalletFunding::where('status', 'successful')->count();
        $fundPending   = WalletFunding::where('status', 'pending')->count();
        $fundFailed    = WalletFunding::where('status', 'failed')->count();

        $total     = $subTotal + $fundTotal;
        $completed = $subCompleted + $fundCompleted;

        // Today's revenue: subscriptions (major) + wallet fundings (minor ÷100)
        $revenueToday = (float) SubscriptionPayment::where('status', 'paid')
                ->whereDate('created_at', $today)->sum('amount')
            + (float) WalletFunding::where('status', 'successful')
                ->whereDate('created_at', $today)->sum('amount') / 100;

        return [
            'revenue'           => number_format($revenueToday, 2),
            'totalTransactions' => $total,
            'completedCount'    => $completed,
            'completedPercent'  => $total > 0 ? round(($completed / $total) * 100) : 0,
            'pendingCount'      => $subPending + $fundPending,
            'failedCount'       => $subFailed + $fundFailed,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('stk_push')
                ->label('STK Push')
                ->form([
                    TextInput::make('phone')->label('Phone')->required()->placeholder('2547XXXXXXXX'),
                    TextInput::make('amount')->label('Amount')->numeric()->required(),
                ])
                ->action(function (array $data) {
                    try {
                        $res = app(MpesaService::class)->stkPush(
                            $data['phone'],
                            $data['amount'],
                            'TanakiHub',
                            'Fleet subscription payment'
                        );

                        if (isset($res['ResponseCode']) && $res['ResponseCode'] == '0') {
                            Transaction::create([
                                'company_id'          => auth()->user()->company_id,
                                'transaction_id'      => 'TXN-' . Str::upper(Str::random(8)),
                                'amount'              => $data['amount'],
                                'phone_number'        => $data['phone'],
                                'checkout_request_id' => $res['CheckoutRequestID'],
                                'merchant_request_id' => $res['MerchantRequestID'] ?? null,
                                'status'              => 'pending',
                                'payment_method'      => 'mpesa',
                            ]);
                            Notification::make()->title('STK Push Sent')->success()->send();
                        } else {
                            Notification::make()->title('STK Push Failed')->body($res['CustomerMessage'] ?? 'Unknown error')->danger()->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
