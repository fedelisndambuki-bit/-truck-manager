<?php

namespace App\Modules\Mpesa;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MpesaService
{
    private $consumerKey;
    private $consumerSecret;
    private $passkey;
    private $shortcode;
    private $env;
    private $baseUrl;
    private $simulate;

    public function __construct()
    {
        $this->consumerKey = config('services.mpesa.key');
        $this->consumerSecret = config('services.mpesa.secret');
        $this->passkey = config('services.mpesa.passkey');
        $this->shortcode = config('services.mpesa.shortcode', '174379');
        $this->env = config('services.mpesa.env', 'sandbox');
        $this->simulate = config('services.mpesa.simulate', false);
        
        $this->baseUrl = $this->env === 'production' 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';
    }

    public function initiateStkPush($phone, $amount, $reference = 'Subscription')
    {
        // Only simulate if NO credentials are provided or if SIMULATE is true
        if ($this->simulate || empty($this->consumerKey) || $this->consumerKey === 'mock_key') {
            Log::info("Simulating STK Push for $phone");
            return [
                'success' => true,
                'data' => [
                    'CheckoutRequestID' => 'ws_CO_SIM_' . time(),
                    'ResponseCode' => '0',
                    'CustomerMessage' => 'Success (SIMULATED)'
                ]
            ];
        }

        $token = $this->generateAccessToken();
        if (!$token) {
            return ['success' => false, 'message' => 'Failed to generate token. Check your credentials.'];
        }

        $timestamp = Carbon::now()->format('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        $callbackUrl = route('api.payments.mpesa.callback');

        try {
            $request = Http::timeout(15)->withToken($token);
            if ($this->env !== 'production') {
                $request->withoutVerifying();
            }

            $response = $request->post($this->baseUrl . '/mpesa/stkpush/v1/processrequest', [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int) $amount,
                'PartyA' => $phone,
                'PartyB' => $this->shortcode,
                'PhoneNumber' => $phone,
                'CallBackURL' => $callbackUrl,
                'AccountReference' => $reference,
                'TransactionDesc' => 'Subscription Payment',
            ]);

            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json()];
            }

            return ['success' => false, 'message' => $response->json('errorMessage') ?? 'STK Push failed'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Connection to Safaricom failed: ' . $e->getMessage()];
        }
    }

    private function generateAccessToken()
    {
        try {
            $request = Http::timeout(5);
            if ($this->env !== 'production') {
                $request->withoutVerifying();
            }

            $response = $request->withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get($this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials');

            return $response->json('access_token');
        } catch (\Exception $e) {
            Log::error("Mpesa Token Error: " . $e->getMessage());
            return null;
        }
    }
}
