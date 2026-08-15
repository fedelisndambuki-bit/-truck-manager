<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMpesaCallbackJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();
        
        Log::info('Mpesa Callback Received', $payload);

        // Webhook Idempotency & Security should be handled here
        // For now, dispatch the job to handle async processing
        ProcessMpesaCallbackJob::dispatch($payload);

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted'
        ]);
    }
}
