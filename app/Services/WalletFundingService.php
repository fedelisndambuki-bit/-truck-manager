<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletFunding;
use App\Services\FinancialService;
use App\Services\PaystackService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class WalletFundingService
{
    public function __construct(
        protected PaystackService  $paystack,
        protected FinancialService $financial,
    ) {}

    /**
     * Initialize wallet funding via Paystack.
     * $amount is in minor units (e.g. 100000 = KES 1,000).
     */
    public function initialize(string $companyId, string $walletId, int $amount, string $currency, int $userId): string
    {
        $reference = 'FUND-' . strtoupper(Str::random(10));

        $funding = WalletFunding::create([
            'id'                  => (string) Str::uuid(),
            'company_id'          => $companyId,
            'wallet_id'           => $walletId,
            'amount'              => $amount,
            'currency'            => $currency,
            'status'              => 'pending',
            'paystack_reference'  => $reference,
            'funded_by_user_id'   => $userId,
        ]);

        $user = auth()->user();

        $authorizationUrl = $this->paystack->initializePayment([
            'email'        => $user->email,
            'amount'       => $amount / 100, // Convert minor units → major units (PaystackService multiplies ×100)
            'reference'    => $reference,
            'callback_url' => route('wallet.funding.callback'),
            'currency'     => $currency,
            'metadata'     => [
                'payment_type' => 'wallet_funding',
                'company_id'   => $companyId,
                'wallet_id'    => $walletId,
                'name'         => $user->name,
            ],
        ]);

        $funding->update(['payment_link' => $authorizationUrl]);

        return $authorizationUrl;
    }

    /**
     * Complete wallet funding using pre-verified webhook data.
     * Use this in the webhook handler where the payload is already signature-verified.
     * Skips the API re-verification call entirely.
     *
     * @param  string $reference  The Paystack reference (e.g. FUND-XXXXXXXXXX)
     * @param  array  $data       The verified 'data' object from the Paystack webhook payload
     */
    public function completeFromWebhookData(string $reference, array $data): WalletFunding
    {
        return DB::transaction(function () use ($reference, $data) {
            $funding = WalletFunding::where('paystack_reference', $reference)->firstOrFail();

            if ($funding->status === 'successful') {
                return $funding;
            }

            if ((int) ($data['amount'] ?? 0) !== (int) $funding->amount) {
                $funding->update([
                    'status'   => 'failed',
                    'metadata' => array_merge($funding->metadata ?? [], ['error' => 'Amount mismatch']),
                ]);
                event(new \App\Events\FundingFailed($funding, 'Amount mismatch.'));
                throw new Exception('Amount mismatch.');
            }

            $funding->update([
                'status'                  => 'successful',
                'paystack_transaction_id' => (string) ($data['id'] ?? ''),
                'completed_at'            => now(),
                'payment_method'          => $data['channel'] ?? 'paystack',
                'metadata'                => $data,
            ]);

            $this->financial->recordTransaction(
                (string) $funding->company_id,
                'wallet_funding',
                (string) $funding->id,
                $this->buildFundingLedgerEntries($funding, $reference),
                "Wallet Preload via Paystack ({$reference})"
            );

            event(new \App\Events\WalletFunded($funding));

            return $funding;
        });
    }

    /**
     * Complete wallet funding after Paystack callback or webhook.
     * $reference is the Paystack reference (e.g. FUND-XXXXXXXXXX).
     * $transactionId is the Paystack numeric transaction ID (optional — used for storage).
     */
    public function complete(string $reference, string $transactionId = ''): WalletFunding
    {
        return DB::transaction(function () use ($reference, $transactionId) {
            $funding = WalletFunding::where('paystack_reference', $reference)->firstOrFail();

            if ($funding->status === 'successful') {
                return $funding;
            }

            // Verify transaction via Paystack (reference-based, not ID-based)
            $verification = $this->paystack->verifyTransaction($reference);

            if (($verification['status'] ?? '') !== 'success') {
                $funding->update(['status' => 'failed']);
                event(new \App\Events\FundingFailed($funding, 'Verification status: ' . ($verification['status'] ?? 'unknown')));
                throw new Exception('Funding verification failed.');
            }

            // Paystack returns amount in smallest unit (kobo = ×100).
            // $funding->amount is also stored in minor units, so compare directly.
            if ((int) $verification['amount'] !== (int) $funding->amount) {
                $funding->update([
                    'status'   => 'failed',
                    'metadata' => array_merge($funding->metadata ?? [], ['error' => 'Amount mismatch']),
                ]);
                event(new \App\Events\FundingFailed($funding, 'Amount mismatch.'));
                throw new Exception('Amount mismatch.');
            }

            $paystackTxId = $transactionId ?: (string) ($verification['id'] ?? '');

            $funding->update([
                'status'                   => 'successful',
                'paystack_transaction_id'  => $paystackTxId,
                'completed_at'             => now(),
                'payment_method'           => $verification['channel'] ?? 'paystack',
                'metadata'                 => $verification,
            ]);

            $this->financial->recordTransaction(
                (string) $funding->company_id,
                'wallet_funding',
                (string) $funding->id,
                $this->buildFundingLedgerEntries($funding, $reference),
                "Wallet Preload via Paystack ({$reference})"
            );

            event(new \App\Events\WalletFunded($funding));

            return $funding;
        });
    }

    /**
     * Build the double-entry ledger entries for a funding transaction.
     *
     * Revenue wallet is asset-like:  debit increases balance.
     * Expense / driver_pool are liability-like: credit increases balance.
     * We flip the polarity accordingly so the correct wallet is credited.
     */
    private function buildFundingLedgerEntries(WalletFunding $funding, string $reference): array
    {
        $walletType = Wallet::find($funding->wallet_id)?->type ?? FinancialService::WALLET_REVENUE;

        if ($walletType === FinancialService::WALLET_REVENUE) {
            // Asset wallet: debit → balance increases
            return [
                [
                    'account_code' => FinancialService::ACC_REVENUE_WALLETS,
                    'debit'        => $funding->amount,
                    'wallet_type'  => FinancialService::WALLET_REVENUE,
                ],
                [
                    'account_code' => FinancialService::ACC_EXTERNAL_FUNDING,
                    'credit'       => $funding->amount,
                ],
            ];
        }

        // Expense / driver_pool wallets are liability-like: credit → balance increases
        return [
            [
                'account_code' => FinancialService::ACC_EXTERNAL_FUNDING,
                'debit'        => $funding->amount,
            ],
            [
                'account_code' => FinancialService::ACC_REVENUE_WALLETS,
                'credit'       => $funding->amount,
                'wallet_type'  => $walletType,
            ],
        ];
    }
}
