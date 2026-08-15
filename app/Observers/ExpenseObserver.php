<?php

namespace App\Observers;

use App\Models\Expense;
use App\Models\Employee;

class ExpenseObserver
{
    /**
     * Compute tax_amount from tax_breakdown before every save.
     */
    public function saving(Expense $expense): void
    {
        if (is_array($expense->tax_breakdown) && count($expense->tax_breakdown) > 0) {
            $expense->tax_amount = collect($expense->tax_breakdown)->sum(fn ($row) => (float) ($row['amount'] ?? 0));
        }
    }

    /**
     * Handle the Expense "created" event.
     */
    public function created(Expense $expense): void
    {
        $this->updateEmployeeFinancials($expense, 'add');
    }

    /**
     * Handle the Expense "updated" event.
     */
    public function updated(Expense $expense): void
    {
        // For updates, we reverse the old transaction and record a new one
        $this->updateEmployeeFinancials($expense, 'update');
    }

    /**
     * Handle the Expense "deleted" event.
     */
    public function deleted(Expense $expense): void
    {
        $this->updateEmployeeFinancials($expense, 'subtract');
    }

    /**
     * Handle the Expense "restored" event.
     */
    public function restored(Expense $expense): void
    {
        $this->updateEmployeeFinancials($expense, 'add');
    }

    /**
     * Adjusts the employee's financial records based on the expense.
     */
    protected function updateEmployeeFinancials(Expense $expense, string $operation): void
    {
        $financialService = app(\App\Services\FinancialService::class);
        $postingEngine = app(\App\Services\PostingEngineService::class);
        $companyId = $expense->company_id;

        if ($operation === 'subtract' || $operation === 'update') {
            // Reversal logic remains same to clear old state before recording new
            $previousEntries = \App\Models\LedgerEntry::where('reference_type', 'expense')
                ->where('reference_id', (string) $expense->id)
                ->get();
            
            foreach ($previousEntries->groupBy('transaction_id') as $txId => $entries) {
                try {
                    $financialService->reverseTransaction($txId, "Expense $operation: ID {$expense->id}");
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to reverse expense transaction $txId: " . $e->getMessage());
                }
            }

            // Reverse the OLD contribution to employee totals before the
            // 'add' branch below applies the new one. On 'update' this must
            // use the pre-change values (getOriginal() — Eloquent hasn't
            // synced originals yet at this point in save()), or every edit
            // just adds on top of the old total instead of replacing it —
            // the same stacking bug moveMoneyToDriverWallet() used to have.
            $oldEmployeeId = $operation === 'update' ? $expense->getOriginal('employee_id') : $expense->employee_id;
            if ($oldEmployeeId) {
                $employee = Employee::find($oldEmployeeId);
                if ($employee) {
                    $oldAmount = (float) ($operation === 'update' ? $expense->getOriginal('amount') : $expense->amount);
                    $oldTaxAmount = (float) ($operation === 'update' ? ($expense->getOriginal('tax_amount') ?? 0) : ($expense->tax_amount ?? 0));
                    $oldCategory = $operation === 'update' ? $expense->getOriginal('category') : $expense->category;

                    if ($oldCategory === 'Salary') {
                        $employee->base_salary -= $oldAmount;
                        $employee->tax_deduction -= $oldTaxAmount;
                    } elseif (in_array($oldCategory, ['Allowance', 'Bonus', 'Commission'])) {
                        $employee->allowances -= $oldAmount;
                    }
                    $employee->save();
                }
            }
        }

        if ($operation === 'add' || $operation === 'update') {
            $amount = (float) $expense->amount;
            $taxAmount = (float) ($expense->tax_amount ?? 0);
            $amountCents = (int) ($amount * 100);

            try {
                // Layer 1: Accounting - Record Liability (Always done)
                $postingEngine->post('expense.recognized', [
                    'company_id' => $companyId ?: 1,
                    'reference_type' => 'expense',
                    'reference_id' => (string) $expense->id,
                    'amount' => $amount,
                    'category' => $expense->category,
                    'stage' => 'recognize',
                    'description' => "Expense Liability Recognized: {$expense->category}",
                ]);

                // Layer 2: Money Movement
                if ($expense->payment_method === 'Wallet' && $expense->employee_id) {
                    // Reconcile the driver wallet to the credit implied by the
                    // CURRENT status (zero when not Paid) instead of blindly
                    // re-crediting the full amount — otherwise editing an
                    // already-Paid commission's amount, or saving it more than
                    // once, stacks duplicate credits instead of replacing them.
                    $targetCents = $expense->status === 'Paid' ? $amountCents : 0;
                    $this->moveMoneyToDriverWallet($expense, $targetCents, $companyId);
                } elseif ($expense->status === 'Paid') {
                    // For other methods, we decrease the Expense Wallet (Asset/Cash reduction)
                    $postingEngine->post('expense.settlement.cash', [
                        'company_id' => $companyId ?: 1,
                        'reference_type' => 'payout',
                        'reference_id' => (string) $expense->id,
                        'amount' => $amount,
                        'category' => $expense->category,
                        'stage' => 'settle_cash',
                        'description' => "Expense Payment Processed: {$expense->category}",
                    ]);
                }

                // Update Employee totals (Accounting layer)
                if ($expense->employee_id) {
                    $employee = Employee::find($expense->employee_id);
                    if ($employee) {
                        if ($expense->category === 'Salary') {
                            $employee->base_salary += $amount;
                            $employee->tax_deduction += $taxAmount;
                        } elseif (in_array($expense->category, ['Allowance', 'Bonus', 'Commission'])) {
                            $employee->allowances += $amount;
                        }
                        $employee->save();
                    }
                }

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to record expense: " . $e->getMessage());
            }
        }
    }

    /**
     * Reconcile the driver wallet to $targetCents (the credit this expense
     * SHOULD represent right now — zero when not Paid) rather than adding
     * $targetCents on top of whatever was already credited. Every prior
     * DriverWalletTransaction tagged with this expense's id is netted out
     * first, so repeated saves of the same Expense (amount edits, status
     * changes) converge on the correct balance instead of stacking credits.
     */
    protected function moveMoneyToDriverWallet(Expense $expense, int $targetCents, $companyId): void
    {
        $employee = Employee::find($expense->employee_id);
        if (!$employee) return;

        $driver = $employee->driver; // real FK bridge to the mobile Driver model
        if (!$driver) return;

        $wallet = \App\Models\DriverWallet::firstOrCreate(
            ['driver_id' => $driver->id],
            [
                'id'         => (string) \Illuminate\Support\Str::uuid(),
                'company_id' => $companyId,
                'balance'    => 0,
                'version'    => 1,
            ]
        );

        $alreadyCredited = (int) \App\Models\DriverWalletTransaction::where('driver_wallet_id', $wallet->id)
            ->where('source_expense_id', (string) $expense->id)
            ->sum('amount');

        $delta = $targetCents - $alreadyCredited;
        if ($delta === 0) return;

        // Map expense category → wallet transaction type
        $type = match (strtolower(trim($expense->category ?? ''))) {
            'salary', 'basic salary', 'basic pay' => 'salary',
            'bonus', 'commission'                  => 'bonus',
            'advance', 'salary advance'            => 'advance',
            default                                => 'allowance',
        };

        // Wrapped in one transaction: if the optimistic-lock retry below
        // exhausts (a concurrent commission posting for the same driver won
        // the race every time) and throws, the GL posting above rolls back
        // with it too — otherwise the ledger would show the payout while
        // the wallet balance/transaction history silently never got it.
        \Illuminate\Support\Facades\DB::transaction(function () use ($expense, $companyId, $employee, $driver, $wallet, $delta, $type) {
            app(\App\Services\PostingEngineService::class)->post('expense.settlement.driver_wallet', [
                'company_id' => $companyId ?: 1,
                'reference_type' => 'internal_transfer',
                'reference_id' => (string) $expense->id,
                'amount' => abs($delta) / 100,
                'category' => $expense->category,
                'stage' => 'settle_driver_wallet',
                'reverse' => $delta < 0,
                'description' => ($delta > 0 ? 'Payment to' : 'Reversal from') . " Driver Wallet: {$employee->full_name}",
            ]);

            $attempts = 0;
            $updated = false;

            while (! $updated && $attempts < 5) {
                $attempts++;
                $current = \App\Models\DriverWallet::where('id', $wallet->id)->first();

                $updated = \App\Models\DriverWallet::where('id', $current->id)
                    ->where('version', $current->version)
                    ->update([
                        'balance'    => $current->balance + $delta,
                        'version'    => $current->version + 1,
                        'updated_at' => now(),
                    ]);
            }

            if (! $updated) {
                throw new \RuntimeException("Concurrency error updating DriverWallet #{$wallet->id} for Expense #{$expense->id} after {$attempts} attempts.");
            }

            \App\Models\DriverWalletTransaction::create([
                'id'                => (string) \Illuminate\Support\Str::uuid(),
                'driver_wallet_id'  => $wallet->id,
                'driver_id'         => $driver->id,
                'company_id'        => $companyId,
                'amount'            => $delta,   // positive = credit, negative = debit
                'type'              => $delta > 0 ? $type : 'adjustment',
                'description'       => $expense->description ?: (ucfirst($type) . ' payment'),
                'source_expense_id' => (string) $expense->id,
            ]);
        });
    }
}