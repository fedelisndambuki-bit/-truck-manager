<?php

namespace Database\Seeders;

use App\Models\PostingRule;
use App\Services\PostingResolvers\ExpensePostingResolver;
use Illuminate\Database\Seeder;

class PostingRuleSeeder extends Seeder
{
    /**
     * Platform-wide (company_id null) default posting rules. A company can
     * override any of these by creating its own row with the same
     * event_key — PostingRule::resolveFor() prefers company-scoped rules.
     */
    public function run(): void
    {
        $rules = [
            ['event_key' => 'expense.recognized', 'resolver_class' => ExpensePostingResolver::class],
            ['event_key' => 'expense.settlement.cash', 'resolver_class' => ExpensePostingResolver::class],
            ['event_key' => 'expense.settlement.driver_wallet', 'resolver_class' => ExpensePostingResolver::class],
            ['event_key' => 'invoice.created', 'debit_account_code' => 1002, 'credit_account_code' => 3001],
            ['event_key' => 'payment.recorded', 'debit_account_code' => 1004, 'credit_account_code' => 1002],
            ['event_key' => 'maintenance.equipment_cost', 'debit_account_code' => 4002, 'credit_account_code' => 2001],
            ['event_key' => 'depreciation.posted', 'debit_account_code' => 4006, 'credit_account_code' => 1005],
            ['event_key' => 'supplier_invoice.approved', 'debit_account_code' => 4007, 'credit_account_code' => 2001],
        ];

        foreach ($rules as $rule) {
            PostingRule::withoutGlobalScope('company')->updateOrCreate(
                ['company_id' => null, 'event_key' => $rule['event_key']],
                array_merge(['is_active' => true, 'priority' => 0], $rule)
            );
        }
    }
}
