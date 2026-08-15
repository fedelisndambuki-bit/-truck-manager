<?php

namespace App\Http\Controllers;

use App\Services\MpesaService;
use Illuminate\Http\Request;

class MpesaController extends Controller
{

    protected $mpesa;

    public function __construct(MpesaService $mpesa)
    {
        $this->mpesa = $mpesa;
    }

    public function pay(Request $request)
    {
        $request->validate([
            'phone' => 'required',
            'amount' => 'required|numeric'
        ]);

        $phone = $request->phone;
        $amount = $request->amount;

        $response = $this->mpesa->stkPush(
            $phone,
            $amount,
            "TanakiHub",
            "Fleet subscription payment"
        );

        if (isset($response['ResponseCode']) && $response['ResponseCode'] == "0") {
            $txn = \App\Modules\Payments\Transaction::create([
                'transaction_id' => 'TXN-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8)),
                'amount' => $amount,
                'phone_number' => $phone,
                'checkout_request_id' => $response['CheckoutRequestID'],
                'merchant_request_id' => $response['MerchantRequestID'] ?? null,
                'status' => 'pending',
                'payment_method' => 'mpesa'
            ]);

            // Optional: Auto-generate invoice for generic payments if no trip is linked
            // This is just a placeholder logic for automation
            \App\Models\Invoice::create([
                'invoice_number' => 'INV-PAY-' . strtoupper(\Illuminate\Support\Str::random(4)),
                'status' => 'paid',
                'total_amount' => $amount,
                'invoice_date' => now(),
                'due_date' => now(),
                'line_items' => [
                    [
                        'description' => 'M-Pesa Payment via ' . $phone,
                        'quantity' => 1,
                        'rate' => $amount,
                    ]
                ],
                'subtotal' => $amount,
                'tax' => 0,
            ]);
        }

        return response()->json($response);
    }
}
