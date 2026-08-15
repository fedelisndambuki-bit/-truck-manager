<?php

namespace App\Services\PostingResolvers;

use App\Services\FinancialService;

/**
 * Reproduces ExpenseObserver's existing category/status branching so
 * re-pointing it at the Posting Engine doesn't change any account it posts
 * to. context: amount, category, stage ('recognize'|'settle_cash'|'settle_driver_wallet').
 */
class ExpensePostingResolver implements PostingRuleResolver
{
    public function resolveLines(array $context): array
    {
        $amount = round((float) ($context['amount'] ?? 0), 2);
        $category = $context['category'] ?? null;
        $stage = $context['stage'] ?? 'recognize';

        $debitAccount = match ($category) {
            'Salary' => 4003,
            'Fuel' => FinancialService::ACC_FUEL_EXPENSE,
            'Repair', 'Maintenance' => FinancialService::ACC_MAINTENANCE_EXPENSE,
            'Commission' => 4003,
            default => FinancialService::ACC_OPERATIONAL_EXPENSES,
        };

        $liabilityAccount = $category === 'Salary'
            ? FinancialService::ACC_DRIVER_LIABILITY
            : FinancialService::ACC_ACCOUNTS_PAYABLE;

        if ($stage === 'recognize') {
            return [
                ['account_code' => $debitAccount, 'debit' => $amount, 'credit' => 0],
                ['account_code' => $liabilityAccount, 'debit' => 0, 'credit' => $amount],
            ];
        }

        if ($stage === 'settle_cash') {
            // Paying off the liability consumes cash the company already set
            // aside in its expense wallet pool — that pool is Liability-like
            // (credit=+, debit=-), so the debit line is the one that must
            // carry the wallet_type tag. Tagging the credit line instead
            // (as this used to) made settling an expense INCREASE the
            // expense wallet rather than draw it down.
            return [
                [
                    'account_code' => $liabilityAccount,
                    'debit' => $amount,
                    'credit' => 0,
                    'wallet_type' => FinancialService::WALLET_EXPENSE,
                ],
                ['account_code' => FinancialService::ACC_BANK_GATEWAY, 'debit' => 0, 'credit' => $amount],
            ];
        }

        // settle_driver_wallet — internal transfer, expense pool to driver pool.
        // reverse=true swaps the direction (driver pool back to expense pool),
        // used when reconciling a Paid commission down after an edit/un-approval.
        $reverse = ! empty($context['reverse']);

        return [
            [
                'account_code' => FinancialService::ACC_DRIVER_LIABILITY,
                'debit' => $reverse ? 0 : $amount,
                'credit' => $reverse ? $amount : 0,
                'wallet_type' => FinancialService::WALLET_EXPENSE,
            ],
            [
                'account_code' => FinancialService::ACC_DRIVER_LIABILITY,
                'debit' => $reverse ? $amount : 0,
                'credit' => $reverse ? 0 : $amount,
                'wallet_type' => FinancialService::WALLET_DRIVER,
            ],
        ];
    }
}
