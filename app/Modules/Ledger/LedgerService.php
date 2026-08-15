<?php

namespace App\Modules\Ledger;

use Illuminate\Support\Facades\DB;

class LedgerService
{
    public function recordEntry($companyId, $referenceId, $debitAccount, $creditAccount, $amount, $description)
    {
        return DB::transaction(function () use ($companyId, $referenceId, $debitAccount, $creditAccount, $amount, $description) {
            // Debit Entry
            LedgerEntry::create([
                'company_id' => $companyId,
                'account_type' => $debitAccount,
                'reference_id' => $referenceId,
                'debit' => $amount,
                'credit' => 0,
                'description' => $description
            ]);

            // Credit Entry
            LedgerEntry::create([
                'company_id' => $companyId,
                'account_type' => $creditAccount,
                'reference_id' => $referenceId,
                'debit' => 0,
                'credit' => $amount,
                'description' => $description
            ]);
        });
    }
}
