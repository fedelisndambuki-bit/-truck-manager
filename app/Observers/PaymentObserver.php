<?php

namespace App\Observers;

use App\Models\Payment;
use App\Models\Invoice;
use App\Models\ProjectFinanceSetting;
use App\Notifications\FinanceNotification;
use App\Services\FinancialService;
use App\Services\FinanceNotificationRecipients;
use App\Services\PostingEngineService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class PaymentObserver
{
    protected FinancialService $financialService;

    public function __construct(FinancialService $financialService, protected FinanceNotificationRecipients $financeRecipients, protected PostingEngineService $postingEngine)
    {
        $this->financialService = $financialService;
    }

    public function created(Payment $payment): void
    {
        $this->recordLedger($payment);
        $this->syncWithInvoice($payment);
        $this->notifyProjectFinance($payment);
    }

    protected function notifyProjectFinance(Payment $payment): void
    {
        if (! $payment->project_id || $payment->status !== 'Paid') {
            return;
        }

        if (! ProjectFinanceSetting::getSetting($payment->company_id, 'notify_payment_received', true)) {
            return;
        }

        $users = $this->financeRecipients->forCompany($payment->company_id);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new FinanceNotification('payment_received', [
            'reference' => session('currency', 'KES') . ' ' . number_format((float) $payment->amount, 0),
            'project_name' => $payment->project?->name,
        ]));
    }

    public function updated(Payment $payment): void
    {
        if ($payment->isDirty('amount') || $payment->isDirty('status')) {
             $this->reverseLedger($payment);
             $this->recordLedger($payment);
             $this->syncWithInvoice($payment);
        }
    }

    public function deleted(Payment $payment): void
    {
        $this->reverseLedger($payment);
    }

    protected function recordLedger(Payment $payment): void
    {
        if ($payment->status !== 'Paid' || $payment->amount <= 0) return;

        try {
            $this->postingEngine->post('payment.recorded', [
                'company_id' => $payment->company_id ?: 1,
                'reference_type' => 'payment',
                'reference_id' => (string) $payment->id,
                'amount' => (float) $payment->amount,
                'description' => "Manual Payment for Invoice: {$payment->invoice_number}",
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to record manual payment in ledger: " . $e->getMessage());
        }
    }

    protected function reverseLedger(Payment $payment): void
    {
        $previousEntries = \App\Models\LedgerEntry::where('reference_type', 'payment')
            ->where('reference_id', (string) $payment->id)
            ->get();

        foreach ($previousEntries->groupBy('transaction_id') as $txId => $entries) {
            // Original entries are never deleted/re-tagged after being
            // reversed, so a second amount/status edit would otherwise find
            // and re-reverse the SAME already-reversed transaction alongside
            // the genuinely new one — reverseTransaction() tags its reversal
            // entries with reference_id = the original transaction_id, so
            // that's a reliable "already reversed" check.
            $alreadyReversed = \App\Models\LedgerEntry::where('reference_type', 'reversal')
                ->where('reference_id', $txId)
                ->exists();

            if ($alreadyReversed) {
                continue;
            }

            try {
                $this->financialService->reverseTransaction($txId, "Manual Payment Reversal: ID {$payment->id}");
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to reverse manual payment transaction $txId: " . $e->getMessage());
            }
        }
    }

    protected function syncWithInvoice(Payment $payment): void
    {
        if ($payment->status !== 'Paid') return;

        // Attempt to find matching invoice
        $invoice = Invoice::where('invoice_number', $payment->invoice_number)->first();
        if ($invoice && $invoice->status !== 'paid') {
            $invoice->update(['status' => 'paid']);
        }
    }
}
