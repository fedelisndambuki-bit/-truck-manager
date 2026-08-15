<?php

namespace App\Http\Controllers;

use App\Models\GatewayPayment;
use App\Models\Invoice;
use App\Models\Payout;
use App\Models\WebhookEvent;
use App\Services\FinancialService;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaystackWebhookController extends Controller
{
    public function __construct(
        private readonly FinancialService $financialService,
        private readonly PaystackService $paystack,
    ) {}

    public function handle(Request $request)
    {
        // ── 1. Verify signature ───────────────────────────────────
        $rawBody   = $request->getContent();
        $signature = $request->header('x-paystack-signature', '');

        if (! $this->paystack->validateWebhookSignature($rawBody, $signature)) {
            Log::warning('Paystack Webhook: Invalid signature.');
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload   = $request->all();
        $event     = $payload['event'] ?? null;
        $data      = $payload['data'] ?? [];

        // Use reference as idempotency key (unique per transaction)
        $reference = $data['reference'] ?? null;

        if (! $reference) {
            return response()->json(['message' => 'Missing reference'], 400);
        }

        // ── 2. Idempotency check ──────────────────────────────────
        if (WebhookEvent::where('source', 'paystack')->where('event_id', $reference)->exists()) {
            return response()->json(['message' => 'Duplicate ignored'], 200);
        }

        // ── 3. Persist event immediately ──────────────────────────
        $webhook = WebhookEvent::create([
            'id'       => (string) Str::uuid(),
            'source'   => 'paystack',
            'event_id' => $reference,
            'payload'  => $payload,
            'status'   => 'pending',
        ]);

        // ── 4. Route by event type ────────────────────────────────
        try {
            match ($event) {
                'charge.success'   => $this->handleChargeSuccess($data),
                'transfer.success' => $this->handleTransferStatus($data, 'completed'),
                'transfer.failed'  => $this->handleTransferStatus($data, 'failed'),
                default            => Log::info("Paystack Webhook: Unhandled event: {$event}"),
            };

            $webhook->update(['status' => 'processed']);
        } catch (\Exception $e) {
            Log::error('Paystack Webhook Error: ' . $e->getMessage(), [
                'event'     => $event,
                'reference' => $reference,
            ]);
            $webhook->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }

        return response()->json(['message' => 'Received'], 200);
    }

    // ─── Event handlers ───────────────────────────────────────────

    private function handleChargeSuccess(array $data): void
    {
        $reference = $data['reference'];

        // Wallet funding — use webhook data directly (signature already verified, no re-verify needed)
        if (str_starts_with($reference, 'FUND-')) {
            app(\App\Services\WalletFundingService::class)->completeFromWebhookData($reference, $data);
            return;
        }

        // Subscription payment — activate via webhook data (no re-verify needed, signature already validated)
        if (str_starts_with($reference, 'SUB_')) {
            app(\App\Services\SubscriptionService::class)->activateFromWebhookData($reference, $data);
            return;
        }

        // Invoice / client payment
        $this->processInvoicePayment($data);
    }

    private function processInvoicePayment(array $data): void
    {
        $reference = $data['reference'];
        // Paystack sends amount in smallest currency unit (kobo) already
        $amount    = (int) ($data['amount']);
        $fee       = (int) ($data['fees'] ?? 0);

        $payment = GatewayPayment::where('tx_ref', $reference)->first();

        if (! $payment) {
            Log::error("Paystack Webhook: GatewayPayment not found for reference: {$reference}");
            return;
        }

        if ($payment->status === 'completed') {
            return;
        }

        $payment->update([
            'status'                 => 'completed',
            'paystack_reference'     => $reference,
            'paystack_transaction_id' => (string) ($data['id'] ?? ''),
            'metadata'               => $data,
        ]);

        $invoice = Invoice::find($payment->invoice_id);
        if ($invoice) {
            $invoice->update(['status' => 'paid']);
        }

        // Double-entry ledger: debit Bank/Gateway (asset), credit Accounts Receivable
        $this->financialService->recordTransaction(
            $payment->company_id,
            'invoice',
            $payment->invoice_id,
            [
                [
                    'account_code' => FinancialService::ACC_BANK_GATEWAY,
                    'debit'        => $amount,
                    'wallet_type'  => FinancialService::WALLET_REVENUE,
                ],
                [
                    'account_code' => FinancialService::ACC_ACCOUNTS_RECEIVABLE,
                    'credit'       => $amount,
                ],
            ],
            "Client Payment for Invoice #{$invoice?->invoice_number} via Paystack"
        );
    }

    private function handleTransferStatus(array $data, string $newStatus): void
    {
        $reference = $data['reference'];

        $payout = Payout::where('idempotency_key', $reference)
            ->orWhere('id', $reference)
            ->first();

        if (! $payout) {
            Log::error("Paystack Webhook: Payout not found for reference: {$reference}");
            return;
        }

        if ($newStatus === 'completed') {
            $payout->update(['status' => 'completed']);
            Log::info("Paystack Webhook: Payout completed for ID: {$payout->id}");
        } else {
            $payout->update(['status' => 'failed', 'last_error' => 'Transfer failed at Paystack']);
            Log::warning("Paystack Webhook: Payout failed for ID: {$payout->id}. Manual review may be required.");
        }
    }
}
