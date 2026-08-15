<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\FinancialService;
use App\Services\PostingEngineService;
use Illuminate\Support\Str;

class InvoiceObserver
{
    protected FinancialService $financialService;

    public function __construct(FinancialService $financialService, protected PostingEngineService $postingEngine)
    {
        $this->financialService = $financialService;
    }

    public function created(Invoice $invoice): void
    {
        $this->recordRevenue($invoice);
    }

    public function updated(Invoice $invoice): void
    {
        if ($invoice->isDirty('total_amount')) {
            // Reverse old revenue and record new one
            $this->reverseRevenue($invoice);
            $this->recordRevenue($invoice);
        }
    }

    public function deleted(Invoice $invoice): void
    {
        $this->reverseRevenue($invoice);
    }

    protected function recordRevenue(Invoice $invoice): void
    {
        if ($invoice->total_amount <= 0) return;

        try {
            $this->postingEngine->post('invoice.created', [
                'company_id' => $invoice->company_id ?: 1,
                'reference_type' => 'invoice',
                'reference_id' => (string) $invoice->id,
                'amount' => (float) $invoice->total_amount,
                'description' => "Revenue Recognized: Invoice #{$invoice->invoice_number}",
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to record invoice revenue: " . $e->getMessage());
        }
    }

    protected function reverseRevenue(Invoice $invoice): void
    {
        $previousEntries = \App\Models\LedgerEntry::where('reference_type', 'invoice')
            ->where('reference_id', (string) $invoice->id)
            ->where('account_code', FinancialService::ACC_TRIP_REVENUE)
            ->get();

        foreach ($previousEntries->groupBy('transaction_id') as $txId => $entries) {
            // Same "already reversed" guard as PaymentObserver::reverseLedger()
            // — without it, a second total_amount edit re-reverses every
            // historical transaction for this invoice, not just the still-
            // active one.
            $alreadyReversed = \App\Models\LedgerEntry::where('reference_type', 'reversal')
                ->where('reference_id', $txId)
                ->exists();

            if ($alreadyReversed) {
                continue;
            }

            try {
                $this->financialService->reverseTransaction($txId, "Invoice Reversal: ID {$invoice->id}");
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to reverse invoice transaction $txId: " . $e->getMessage());
            }
        }
    }
}
