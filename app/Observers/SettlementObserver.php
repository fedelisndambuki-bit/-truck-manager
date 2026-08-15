<?php

namespace App\Observers;

use App\Models\Settlement;
use App\Models\Expense;

class SettlementObserver
{
    /**
     * Handle the Settlement "created" event — a settlement can be created
     * with status already PAID (SettlementResource's create form allows
     * it directly), which used to never pay the driver at all since only
     * updated() looked for a status transition.
     */
    public function created(Settlement $settlement): void
    {
        if ($settlement->status === 'PAID') {
            $this->createWalletExpense($settlement);
        }
    }

    /**
     * Handle the Settlement "updated" event.
     */
    public function updated(Settlement $settlement): void
    {
        // When a settlement is marked as PAID, we create an Expense record
        // with payment_method 'Wallet' so it hits the driver's wallet.
        if ($settlement->isDirty('status') && $settlement->status === 'PAID') {
            $this->createWalletExpense($settlement);

            return;
        }

        // Correcting the amount on an already-Paid settlement used to never
        // reach the linked Expense/ledger/wallet at all — sync it instead.
        if ($settlement->status === 'PAID' && $settlement->isDirty('net_amount')) {
            $this->syncWalletExpense($settlement);
        }
    }

    protected function createWalletExpense(Settlement $settlement): void
    {
        // Exact FK match — the description previously used for dedup
        // (LIKE '%Settlement #{id}%') is ambiguous across ids that share a
        // prefix (e.g. #1 matches #17).
        if (Expense::where('settlement_id', $settlement->id)->exists()) {
            return;
        }

        Expense::create([
            'company_id' => $settlement->company_id ?: 1,
            'employee_id' => $settlement->employee_id,
            'truck_id' => $settlement->truck_id,
            'settlement_id' => $settlement->id,
            'category' => 'Salary',
            'amount' => $settlement->net_amount,
            'date' => $settlement->settlement_date ?: now(),
            'payment_method' => 'Wallet',
            'status' => 'Paid',
            'payslip_earning_type' => 'basic_pay',
            'description' => "Settlement #{$settlement->settlement_id} for period {$settlement->pay_period_start->format('d/m/Y')} - {$settlement->pay_period_end->format('d/m/Y')}"
        ]);
    }

    protected function syncWalletExpense(Settlement $settlement): void
    {
        $expense = Expense::where('settlement_id', $settlement->id)->first();
        if (! $expense) return;

        $expense->update(['amount' => $settlement->net_amount]);
    }
}
