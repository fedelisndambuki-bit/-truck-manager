<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WebhookEvent;
use App\Models\GatewayPayment;
use App\Models\Payout;
use App\Models\Invoice;
use App\Services\FinancialService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FlutterwaveWebhookController extends Controller
{
    private $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function handle(Request $request)
    {
        $signature = $request->header('verif-hash');
        $secretHash = config('services.flutterwave.webhook_hash');

        if (!$signature || ($signature !== $secretHash)) {
            Log::warning("Flutterwave Webhook Unauthorized: Missing or invalid signature.");
            // return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        $eventId = $payload['id'] ?? ($payload['data']['id'] ?? null);

        if (!$eventId) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // 1. Idempotency Check
        $exists = WebhookEvent::where('source', 'flutterwave')->where('event_id', $eventId)->exists();
        if ($exists) {
            return response()->json(['message' => 'Duplicate ignored'], 200);
        }

        // 2. Immediate Persistence
        $webhook = WebhookEvent::create([
            'id' => (string) Str::uuid(),
            'source' => 'flutterwave',
            'event_id' => $eventId,
            'payload' => $payload,
            'status' => 'pending'
        ]);

        // 3. Process Event
        try {
            $event = $payload['event'] ?? null;
            
            switch ($event) {
                case 'charge.completed':
                    if ($payload['data']['status'] === 'successful') {
                        $txRef = $payload['data']['tx_ref'];
                        if (str_starts_with($txRef, 'FUND-')) {
                            app(\App\Services\WalletFundingService::class)->complete($txRef, (string)$payload['data']['id']);
                        } else {
                            $this->processSuccessfulPayment($payload['data']);
                        }
                    }
                    break;
                    
                case 'transfer.completed':
                    $this->processSuccessfulTransfer($payload['data']);
                    break;

                default:
                    Log::info("Flutterwave Webhook: Unhandled event type: $event");
            }
            
            $webhook->update(['status' => 'processed']);
        } catch (\Exception $e) {
            Log::error("Flutterwave Webhook Error: " . $e->getMessage());
            $webhook->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }

        return response()->json(['message' => 'Received'], 200);
    }

    /**
     * Handle incoming client payments.
     */
    private function processSuccessfulPayment(array $data)
    {
        $txRef = $data['tx_ref'];
        $amount = (int) ($data['amount'] * 100); // Convert to minor units
        $fee = (int) (($data['app_fee'] ?? 0) * 100);
        
        $payment = GatewayPayment::where('tx_ref', $txRef)->first();
        if (!$payment) {
            Log::error("Payment not found for TxRef: $txRef");
            return;
        }

        if ($payment->status === 'completed') return;

        $payment->update([
            'status' => 'completed',
            'flw_tx_id' => $data['id'],
            'metadata' => $data
        ]);

        // Update Invoice
        $invoice = Invoice::find($payment->invoice_id);
        if ($invoice) {
            $invoice->update(['status' => 'paid']);
        }

        // Ledger Posting:
        // Debit Bank/Gateway (Asset)
        // Credit Trip Revenue (Income)
        $this->financialService->recordTransaction(
            $payment->company_id,
            'invoice',
            $payment->invoice_id,
            [
                [
                    'account_code' => FinancialService::ACC_BANK_GATEWAY, 
                    'debit' => $amount,
                    'wallet_type' => FinancialService::WALLET_REVENUE // Credit Revenue Wallet
                ],
                [
                    'account_code' => FinancialService::ACC_ACCOUNTS_RECEIVABLE, 
                    'credit' => $amount
                ]
            ],
            "Client Payment for Invoice #{$invoice?->invoice_number} via Flutterwave"
        );
    }

    /**
     * Handle outgoing payouts (Transfers).
     */
    private function processSuccessfulTransfer(array $data)
    {
        $reference = $data['reference']; // This is our Payout ID or Idempotency Key
        $status = $data['status'];

        $payout = Payout::where('idempotency_key', $reference)->orWhere('id', $reference)->first();
        if (!$payout) {
            Log::error("Payout not found for reference: $reference");
            return;
        }

        if ($status === 'SUCCESSFUL') {
            $payout->update(['status' => 'completed']);
            Log::info("Payout completed for ID: {$payout->id}");
        } elseif ($status === 'FAILED') {
            $payout->update(['status' => 'failed', 'last_error' => 'Transfer failed at gateway']);
            
            // Reversal Logic:
            // Debit Bank/Gateway (Asset)
            // Credit Liability (Restore debt)
            // This is complex and should ideally be a separate method in FinancialService
            Log::warning("Payout failed at gateway: {$payout->id}. Manual reversal required or implement auto-reversal.");
        }
    }
}

