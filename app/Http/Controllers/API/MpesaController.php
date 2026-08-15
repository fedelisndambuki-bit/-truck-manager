<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Mpesa\MpesaService;
use App\Modules\Payments\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MpesaController extends Controller
{
    private $mpesaService;

    public function __construct(MpesaService $mpesaService)
    {
        $this->mpesaService = $mpesaService;
    }

    public function stkPush(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'company_id' => 'required|integer' // Must pass company_id for API calls
        ]);

        $res = $this->mpesaService->initiateStkPush($request->phone, $request->amount);

        if ($res['success']) {
            Transaction::create([
                'company_id' => $request->company_id,
                'transaction_id' => 'TXN-' . Str::upper(Str::random(8)),
                'amount' => $request->amount,
                'payment_method' => 'mpesa',
                'gateway_reference' => $res['data']['CheckoutRequestID'],
                'status' => 'pending'
            ]);

            return response()->json($res['data']);
        }

        return response()->json(['message' => $res['message']], 400);
    }
}
