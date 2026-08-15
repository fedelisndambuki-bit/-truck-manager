<?php

namespace App\Services;

use App\Models\DriverWallet;
use App\Models\Payout;
use App\Services\FinancialService;
use App\Services\PaystackService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class PayoutService
{
    public function __construct(
        private readonly FinancialService $financialService,
        private readonly PaystackService  $paystack,
    ) {}

    /**
     * Create a pending payout record (no transfer yet).
     */
    public function initiatePayout(array $data): Payout
    {
        return Payout::create([
            'id'              => (string) Str::uuid(),
            'company_id'      => $data['company_id'],
            'recipient_id'    => $data['recipient_id'],
            'recipient_type'  => $data['recipient_type'], // driver, vendor
            'amount'          => $data['amount'],          // minor units (cents)
            'currency'        => $data['currency'] ?? 'KES',
            'bank_code'       => $data['bank_code'],
            'account_number'  => $data['account_number'],
            'idempotency_key' => $data['idempotency_key'] ?? (string) Str::uuid(),
            'status'          => 'pending',
        ]);
    }

    /**
     * Approve and execute a pending payout via Paystack.
     */
    public function approveAndExecute(string $payoutId, string $actorId): void
    {
        $payout = Payout::findOrFail($payoutId);

        if ($payout->status !== 'pending') {
            throw new Exception('Payout is already ' . $payout->status);
        }

        // Determine wallet type and liability account
        $walletType       = FinancialService::WALLET_EXPENSE;
        $liabilityAccount = FinancialService::ACC_ACCOUNTS_PAYABLE;

        if ($payout->recipient_type === 'driver') {
            $walletType       = FinancialService::WALLET_DRIVER;
            $liabilityAccount = FinancialService::ACC_DRIVER_LIABILITY;
        }

        // For driver payouts, this pays out money already sitting in the
        // driver's own wallet (the same balance withdrawToMpesa() draws
        // from) — without debiting it here too, the driver's app keeps
        // showing the full balance as available and could self-withdraw
        // the same money again after the company already paid it out.
        $driverWallet = null;
        if ($payout->recipient_type === 'driver') {
            $driverWallet = DriverWallet::where('driver_id', $payout->recipient_id)->first();

            if (! $driverWallet || $driverWallet->balance < $payout->amount) {
                throw new Exception('Insufficient funds in driver wallet.');
            }
        }

        // Record double-entry ledger entry
        $this->financialService->recordTransaction(
            $payout->company_id,
            'payout',
            $payout->id,
            [
                [
                    'account_code' => $liabilityAccount,
                    'debit'        => $payout->amount,
                    'wallet_type'  => $walletType,
                ],
                [
                    'account_code' => FinancialService::ACC_BANK_GATEWAY,
                    'credit'       => $payout->amount,
                ],
            ],
            "Payout Approved for {$payout->recipient_type} #{$payout->recipient_id}"
        );

        if ($driverWallet) {
            $updated = DriverWallet::where('id', $driverWallet->id)
                ->where('version', $driverWallet->version)
                ->update([
                    'balance'    => $driverWallet->balance - $payout->amount,
                    'version'    => $driverWallet->version + 1,
                    'updated_at' => now(),
                ]);

            if (! $updated) {
                throw new Exception('Concurrency error updating driver wallet. Please retry.');
            }

            \App\Models\DriverWalletTransaction::create([
                'id'                => (string) Str::uuid(),
                'driver_wallet_id'  => $driverWallet->id,
                'driver_id'         => $driverWallet->driver_id,
                'company_id'        => $payout->company_id,
                'amount'            => -$payout->amount, // negative = debit
                'type'              => 'payout',
                'description'       => "Payout #{$payout->id} approved and paid out",
                'source_expense_id' => null,
            ]);
        }

        // Create Paystack transfer recipient, then initiate transfer
        $recipientCode = $this->paystack->createTransferRecipient([
            'type'           => $this->resolveRecipientType($payout->bank_code),
            'name'           => "Payout #{$payout->id}",
            'account_number' => $payout->account_number,
            'bank_code'      => $payout->bank_code,
            'currency'       => $payout->currency,
        ]);

        $transfer = $this->paystack->initiateTransfer([
            'amount'    => $payout->amount / 100, // Convert minor units → major units (PaystackService ×100)
            'recipient' => $recipientCode,
            'reference' => $payout->idempotency_key,
            'reason'    => "Tanaki Hub Payout — Ref: {$payout->id}",
            'currency'  => $payout->currency,
        ]);

        $payout->update([
            'status'                   => 'processing',
            'paystack_recipient_code'  => $recipientCode,
            'paystack_transfer_code'   => $transfer['transfer_code'] ?? null,
        ]);
    }

    /**
     * Handle driver self-withdrawal to M-Pesa.
     */
    public function withdrawToMpesa(\App\Models\Driver $driver, int $amountCents, string $phoneNumber): Payout
    {
        return DB::transaction(function () use ($driver, $amountCents, $phoneNumber) {
            $wallet = DriverWallet::where('driver_id', $driver->id)->first();

            if (! $wallet || $wallet->balance < $amountCents) {
                throw new Exception('Insufficient funds in driver wallet.');
            }

            // Normalize phone to Paystack international format (2547XXXXXXXX)
            $normalizedPhone = $this->normalizeKenyanPhone($phoneNumber);

            // 1. Create Payout record
            $payout = Payout::create([
                'id'              => (string) Str::uuid(),
                'company_id'      => $driver->company_id,
                'recipient_id'    => (string) $driver->id,
                'recipient_type'  => 'driver',
                'amount'          => $amountCents,
                'currency'        => 'KES',
                'bank_code'       => 'MPESA',
                'account_number'  => $normalizedPhone,
                'idempotency_key' => (string) Str::uuid(),
                'status'          => 'processing',
            ]);

            // 2. Ledger & wallet updates
            $this->financialService->recordTransaction(
                $driver->company_id,
                'withdrawal',
                $payout->id,
                [
                    [
                        'account_code' => FinancialService::ACC_DRIVER_LIABILITY,
                        'debit'        => $amountCents,
                        'wallet_type'  => FinancialService::WALLET_DRIVER,
                    ],
                    [
                        'account_code' => FinancialService::ACC_BANK_GATEWAY,
                        'credit'       => $amountCents,
                    ],
                ],
                "Self-withdrawal to M-Pesa: {$normalizedPhone}"
            );

            // 3. Optimistic locking on driver wallet balance
            $updated = DriverWallet::where('id', $wallet->id)
                ->where('version', $wallet->version)
                ->update([
                    'balance'    => $wallet->balance - $amountCents,
                    'version'    => $wallet->version + 1,
                    'updated_at' => now(),
                ]);

            if (! $updated) {
                throw new Exception('Concurrency error updating driver wallet. Please retry.');
            }

            // Record labeled withdrawal transaction in driver wallet history
            \App\Models\DriverWalletTransaction::create([
                'id'               => (string) Str::uuid(),
                'driver_wallet_id' => $wallet->id,
                'driver_id'        => $driver->id,
                'company_id'       => $driver->company_id,
                'amount'           => -$amountCents,  // negative = debit
                'type'             => 'withdrawal',
                'description'      => "M-Pesa withdrawal to {$normalizedPhone}",
            ]);

            // 4. Create Paystack transfer recipient (M-Pesa)
            $recipientCode = $this->paystack->createTransferRecipient([
                'type'           => 'mobile_money',
                'name'           => "Driver {$driver->id} M-Pesa",
                'account_number' => $normalizedPhone,
                'bank_code'      => 'MPESA',
                'currency'       => 'KES',
            ]);

            // 5. Initiate Paystack transfer
            $transfer = $this->paystack->initiateTransfer([
                'amount'    => $amountCents / 100, // minor → major (PaystackService ×100)
                'recipient' => $recipientCode,
                'reference' => $payout->idempotency_key,
                'reason'    => "Tanaki Hub Withdrawal — Driver: {$driver->id}",
                'currency'  => 'KES',
            ]);

            $payout->update([
                'paystack_recipient_code' => $recipientCode,
                'paystack_transfer_code'  => $transfer['transfer_code'] ?? null,
            ]);

            return $payout;
        });
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Map bank code to Paystack recipient type.
     * M-Pesa transfers use 'mobile_money'; everything else uses 'nuban' (bank account).
     */
    private function resolveRecipientType(string $bankCode): string
    {
        return strtoupper($bankCode) === 'MPESA' ? 'mobile_money' : 'nuban';
    }

    /**
     * Normalize a Kenyan phone number to Paystack's required format: 2547XXXXXXXX.
     */
    private function normalizeKenyanPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        } elseif (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
            $phone = '254' . $phone;
        }

        return $phone;
    }
}
