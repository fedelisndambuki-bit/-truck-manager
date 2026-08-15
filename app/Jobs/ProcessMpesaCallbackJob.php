<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Modules\Payments\Transaction;
use App\Modules\Mpesa\MpesaCallback;
use App\Modules\Ledger\LedgerService;

class ProcessMpesaCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle(LedgerService $ledger)
    {
        $data = $this->payload['Body']['stkCallback'];
        
        MpesaCallback::create([
            'checkout_request_id' => $data['CheckoutRequestID'],
            'merchant_request_id' => $data['MerchantRequestID'],
            'result_code' => $data['ResultCode'],
            'result_desc' => $data['ResultDesc'],
            'raw_payload' => $this->payload
        ]);

        $txn = Transaction::withoutGlobalScopes()->where('gateway_reference', $data['CheckoutRequestID'])->first();

        if ($data['ResultCode'] == 0 && $txn) {
            $txn->update(['status' => 'completed']);

            // Record Initial Ledger Entry (Cash Received)
            $ledger->recordEntry(
                $txn->company_id,
                $txn->transaction_id,
                'cash', 
                'customer_liability', 
                $txn->amount, 
                "Payment Received"
            );
        } elseif ($txn) {
            $txn->update(['status' => 'failed']);
        }
    }
}
