<?php

namespace App\Services;

use App\Models\GatewayPayment;
use App\Models\Invoice;
use App\Services\FinancialService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Exception;

class PaymentService
{
    private $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * Initialize a Flutterwave Standard Checkout payment for an invoice.
     */
    public function initializePayment(string $invoiceId, string $companyId)
    {
        $invoice = Invoice::findOrFail($invoiceId);
        
        if ($invoice->status === 'paid') {
            throw new Exception("Invoice is already paid.");
        }

        $txRef = 'INV-' . $invoice->id . '-' . time();

        // Create a pending payment record
        $payment = GatewayPayment::create([
            'id' => (string) Str::uuid(),
            'company_id' => $companyId,
            'invoice_id' => $invoice->id,
            'amount' => $invoice->total_amount, // Assuming this is in minor units or converted later
            'currency' => $invoice->currency ?? 'KES',
            'status' => 'pending',
            'tx_ref' => $txRef,
        ]);


        $response = Http::withToken(config('services.flutterwave.secret_key'))
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $txRef,
                'amount' => $invoice->total_amount / 100, // Flutterwave expects major units
                'currency' => $invoice->currency ?? 'KES',
                'redirect_url' => route('payments.callback'),
                'customer' => [
                    'email' => $invoice->client->email ?? 'customer@example.com',
                    'name' => $invoice->client->name ?? 'Customer',
                ],
                'customizations' => [
                    'title' => 'Invoice Payment - ' . $invoice->invoice_number,
                    'description' => 'Payment for services rendered',
                ],
            ]);

        if ($response->successful()) {
            return $response->json('data.link');
        }

        throw new Exception("Flutterwave initialization failed: " . $response->body());
    }

    /**
     * Handle payment confirmation from webhook or callback.
     */
    public function confirmPayment(string $txRef, string $flwTxId)
    {
        $payment = GatewayPayment::where('tx_ref', $txRef)->firstOrFail();

        if ($payment->status === 'completed') {
            return $payment;
        }


        // Verify with Flutterwave
        $response = Http::withToken(config('services.flutterwave.secret_key'))
            ->get("https://api.flutterwave.com/v3/transactions/{$flwTxId}/verify");

        if ($response->successful() && $response->json('data.status') === 'successful') {
            $data = $response->json('data');
            
            // 1. Update Payment record
            $payment->update([
                'status' => 'completed',
                'flw_tx_id' => $flwTxId,
                'metadata' => json_encode($data)
            ]);

            // 2. Update Invoice status
            $invoice = Invoice::find($payment->invoice_id);
            if ($invoice) {
                $invoice->update(['status' => 'paid']);
            }

            // 3. Record in Ledger
            $this->financialService->recordTransaction(
                $payment->company_id,
                'payment',
                $payment->id,
                [
                    [
                        'account_code' => FinancialService::ACC_BANK_GATEWAY, 
                        'debit' => $payment->amount,
                        'wallet_type' => FinancialService::WALLET_REVENUE // Credit Revenue Wallet
                    ],
                    [
                        'account_code' => FinancialService::ACC_ACCOUNTS_RECEIVABLE, 
                        'credit' => $payment->amount
                    ]
                ],
                "Payment received for Invoice #{$invoice->invoice_number}"
            );

            return $payment;
        }

        $payment->update(['status' => 'failed']);
        throw new Exception("Payment verification failed for Ref: $txRef");
    }
}
