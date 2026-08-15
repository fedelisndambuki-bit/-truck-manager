<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class FinancialService
{
    /**
     * Account Codes (Chart of Accounts)
     * 1000-1999: Assets
     * 2000-2999: Liabilities
     * 3000-3999: Income
     * 4000-4999: Expenses
     */
    const ACC_BANK_GATEWAY = 1001;      // Asset: Funds held at Flutterwave
    const ACC_ACCOUNTS_RECEIVABLE = 1002; // Asset: Money owed by Clients
    const ACC_REVENUE_WALLETS = 1003;    // Asset: Internal revenue tracker
    const ACC_CASH_ON_HAND = 1004;      // Asset: Cash in hand/Manual bank deposits
    
    const ACC_ACCOUNTS_PAYABLE = 2001;   // Liability: Money owed to Vendors
    const ACC_DRIVER_LIABILITY = 2002;   // Liability: Money owed to Drivers (Driver Wallet)
    const ACC_GATEWAY_FEES_PAYABLE = 2003; // Liability: Fees owed to gateway
    
    const ACC_TRIP_REVENUE = 3001;      // Income: Revenue from trips
    const ACC_CONVENIENCE_FEES = 3002;   // Income: Fees charged to users
    const ACC_EXTERNAL_FUNDING = 3003;   // Income: External wallet funding
    
    const ACC_FUEL_EXPENSE = 4001;       // Expense: Fuel costs
    const ACC_MAINTENANCE_EXPENSE = 4002; // Expense: Repair costs
    const ACC_OPERATIONAL_EXPENSES = 4003; // Expense: Other operational costs
    const ACC_GATEWAY_FEES_EXPENSE = 4004; // Expense: Payment processing fees
    const ACC_FLUTTERWAVE_CLEARING = 4005; // Expense/Clearing: Outgoing transfer clearing account

    /**
     * Wallet Types
     */
    const WALLET_REVENUE = 'revenue';
    const WALLET_EXPENSE = 'expense';
    const WALLET_DRIVER = 'driver_pool';

    /**
     * Record a double-entry transaction and update wallets atomically.
     * 
     * @param string $companyId
     * @param string $referenceType (invoice, trip, expense, payout)
     * @param string $referenceId
     * @param array $entries Array of ['account_code' => int, 'debit' => int, 'credit' => int, 'wallet_type' => string|null]
     * @param string $description
     * @return string Transaction ID
     * @throws Exception
     */
    public function recordTransaction(string $companyId, string $referenceType, string $referenceId, array $entries, string $description)
    {
        return DB::transaction(function () use ($companyId, $referenceType, $referenceId, $entries, $description) {
            $transactionId = (string) Str::uuid();
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($entries as $entry) {
                $debit = $entry['debit'] ?? 0;
                $credit = $entry['credit'] ?? 0;
                
                $totalDebit += $debit;
                $totalCredit += $credit;

                LedgerEntry::create([
                    'id' => (string) Str::uuid(),
                    'transaction_id' => $transactionId,
                    'company_id' => $companyId,
                    'account_code' => $entry['account_code'],
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'debit' => $debit,
                    'credit' => $credit,
                    'wallet_type' => $entry['wallet_type'] ?? null,
                    'description' => $description,
                ]);

                // If entry affects a wallet, update it
                if (!empty($entry['wallet_type'])) {
                    $this->updateWalletBalance(
                        $companyId, 
                        $entry['wallet_type'], 
                        $debit,
                        $credit
                    );
                }
            }

            // Double-entry check
            if ($totalDebit !== $totalCredit) {
                throw new Exception("Ledger imbalance: Debits ($totalDebit) != Credits ($totalCredit)");
            }

            return $transactionId;
        });
    }

    /**
     * Update wallet balance with optimistic locking and sub-wallet logic.
     * 
     * @param string $companyId
     * @param string $type
     * @param int $debit
     * @param int $credit
     * @throws Exception
     */
    private function updateWalletBalance(string $companyId, string $type, int $debit, int $credit)
    {
        $wallet = Wallet::where('company_id', $companyId)->where('type', $type)->first();
        
        if (!$wallet) {
            // Auto-create wallet if it doesn't exist (e.g., first transaction)
            $wallet = Wallet::create([
                'id' => (string) Str::uuid(),
                'company_id' => $companyId,
                'type' => $type,
                'available_balance' => 0,
                'ledger_balance' => 0,
                'pending_holds' => 0,
                'version' => 1
            ]);
        }

        /**
         * Logic:
         * Assets (1000s): Debit increases, Credit decreases.
         * Liabilities (2000s): Credit increases, Debit decreases.
         * Our internal wallets behave like Liabilities (the company owes these funds to the driver pool, etc.)
         * OR Assets (Revenue wallet represents money the company has).
         * 
         * For simplicity in this implementation:
         * Revenue Wallet (Asset-like): Debit (+) / Credit (-)
         * Expense/Driver Wallets (Liability-like): Credit (+) / Debit (-)
         */
        
        $netChange = 0;
        if ($type === self::WALLET_REVENUE) {
            $netChange = $debit - $credit;
        } else {
            // Expense and Driver wallets are Liability-like
            $netChange = $credit - $debit;
        }

        $newAvailableBalance = $wallet->available_balance + $netChange;
        $newLedgerBalance = $wallet->ledger_balance + $netChange;
        
        // Prevent negative balances for non-revenue wallets if required
        if ($newAvailableBalance < 0 && $type !== self::WALLET_REVENUE) {
             throw new Exception("Insufficient funds in $type wallet (Available: {$wallet->available_balance}, Net Change: $netChange)");
        }

        $updated = Wallet::where('id', $wallet->id)
            ->where('version', $wallet->version)
            ->update([
                'available_balance' => $newAvailableBalance,
                'ledger_balance' => $newLedgerBalance,
                'version' => $wallet->version + 1,
                'updated_at' => now()
            ]);

        if (!$updated) {
            throw new Exception("Concurrency error updating $type wallet. Please retry.");
        }
    }

    /**
     * Get the balance of a specific account code.
     */
    public function getAccountBalance(string $companyId, int $accountCode)
    {
        $totals = LedgerEntry::where('company_id', $companyId)
            ->where('account_code', $accountCode)
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        if ($accountCode >= 1000 && $accountCode < 2000) { // Asset
            return ($totals->total_debit ?? 0) - ($totals->total_credit ?? 0);
        }
        
        if ($accountCode >= 2000 && $accountCode < 3000) { // Liability
            return ($totals->total_credit ?? 0) - ($totals->total_debit ?? 0);
        }

        return ($totals->total_credit ?? 0) - ($totals->total_debit ?? 0); // Default (Income/Equity)
    }

    /**
     * Reverse a transaction by creating an exact opposite entry.
     * 
     * @param string $transactionId
     * @param string $reason
     * @throws Exception
     */
    public function reverseTransaction(string $transactionId, string $reason = 'Reversal')
    {
        return DB::transaction(function () use ($transactionId, $reason) {
            $originalEntries = LedgerEntry::where('transaction_id', $transactionId)->get();
            
            if ($originalEntries->isEmpty()) {
                throw new Exception("Transaction $transactionId not found.");
            }

            $newTransactionId = (string) Str::uuid();
            $companyId = $originalEntries->first()->company_id;

            foreach ($originalEntries as $entry) {
                $walletType = $entry->wallet_type;

                LedgerEntry::create([
                    'id' => (string) Str::uuid(),
                    'transaction_id' => $newTransactionId,
                    'company_id' => $companyId,
                    'account_code' => $entry->account_code,
                    'reference_type' => 'reversal',
                    'reference_id' => $transactionId,
                    'debit' => $entry->credit, // Swap Debit and Credit
                    'credit' => $entry->debit,
                    'wallet_type' => $walletType,
                    'description' => "REVERSAL: $reason | Original ID: $transactionId",
                ]);

                if ($walletType) {
                    $this->updateWalletBalance(
                        $companyId,
                        $walletType,
                        $entry->credit, // Debit
                        $entry->debit  // Credit
                    );
                }
            }

            return $newTransactionId;
        });
    }
}


