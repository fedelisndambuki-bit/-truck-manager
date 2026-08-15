<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Platform-wide (company_id null) system accounts. Codes 1001-4005
     * match FinancialService's ACC_* constants exactly, so existing
     * fin_ledger_entries.account_code values keep resolving.
     */
    public function run(): void
    {
        $accounts = [
            [1001, 'Bank / Gateway', 'Asset', 'Debit'],
            [1002, 'Accounts Receivable', 'Asset', 'Debit'],
            [1003, 'Revenue Wallets', 'Asset', 'Debit'],
            [1004, 'Cash on Hand', 'Asset', 'Debit'],
            [1005, 'Accumulated Depreciation', 'Asset', 'Credit'],
            [2001, 'Accounts Payable', 'Liability', 'Credit'],
            [2002, 'Driver Liability', 'Liability', 'Credit'],
            [2003, 'Gateway Fees Payable', 'Liability', 'Credit'],
            [2004, 'Goods Received Not Invoiced', 'Liability', 'Credit'],
            [3001, 'Trip Revenue', 'Revenue', 'Credit'],
            [3002, 'Convenience Fees', 'Revenue', 'Credit'],
            [3003, 'External Funding', 'Revenue', 'Credit'],
            [4001, 'Fuel Expense', 'Expense', 'Debit'],
            [4002, 'Maintenance Expense', 'Expense', 'Debit'],
            [4003, 'Operational Expenses', 'Expense', 'Debit'],
            [4004, 'Gateway Fees Expense', 'Expense', 'Debit'],
            [4005, 'Flutterwave Clearing', 'Expense', 'Debit'],
            [4006, 'Depreciation Expense', 'Expense', 'Debit'],
            [4007, 'Purchases / Inventory', 'Expense', 'Debit'],
        ];

        foreach ($accounts as [$code, $name, $type, $normalBalance]) {
            ChartOfAccount::withoutGlobalScope('company')->updateOrCreate(
                ['account_code' => $code],
                [
                    'company_id' => null,
                    'name' => $name,
                    'type' => $type,
                    'normal_balance' => $normalBalance,
                    'currency' => 'KES',
                    'status' => 'Active',
                    'is_system' => true,
                ]
            );
        }
    }
}
