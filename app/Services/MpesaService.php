<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MpesaService
{

    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('mpesa.env') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    /**
     * Get OAuth Access Token
     */
    public function getAccessToken()
    {
        $request = Http::withBasicAuth(
            config('mpesa.consumer_key'),
            config('mpesa.consumer_secret')
        );

        if (config('mpesa.env') !== 'production') {
            $request->withoutVerifying();
        }

        $response = $request->get($this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials');

        if (!$response->successful()) {
            Log::error('MPESA TOKEN ERROR', [
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
                'url' => $this->baseUrl . '/oauth/v1/generate?grant_type=client_credentials'
            ]);
            throw new \Exception('Unable to generate access token');
        }

        $token = $response['access_token'];
        Log::info('MPESA TOKEN GENERATED', ['token_start' => substr($token, 0, 5) . '...']);

        return $token;
    }

    /**
     * Generate Timestamp
     */
    protected function generateTimestamp()
    {
        return now()->format('YmdHis');
    }

    /**
     * Generate Password
     */
    protected function generatePassword($timestamp)
    {
        $shortcode = config('mpesa.shortcode');
        $passkey = config('mpesa.passkey');

        return base64_encode($shortcode . $passkey . $timestamp);
    }

    /**
     * Initiate STK Push
     */
    public function stkPush($phone, $amount, $accountReference, $description)
    {
        // Format phone number
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        } elseif (str_starts_with($phone, '+254')) {
            $phone = substr($phone, 1);
        } elseif (strlen($phone) == 9) {
            $phone = '254' . $phone;
        }

        $token = $this->getAccessToken();

        $timestamp = $this->generateTimestamp();

        $password = $this->generatePassword($timestamp);

        $payload = [

            "BusinessShortCode" => config('mpesa.shortcode'),

            "Password" => $password,

            "Timestamp" => $timestamp,

            "TransactionType" => "CustomerPayBillOnline",

            "Amount" => (int) $amount,

            "PartyA" => $phone,

            "PartyB" => config('mpesa.shortcode'),

            "PhoneNumber" => $phone,

            "CallBackURL" => config('mpesa.callback_url'),

            "AccountReference" => $accountReference,

            "TransactionDesc" => $description

        ];

        $request = Http::withToken($token);

        if (config('mpesa.env') !== 'production') {
            $request->withoutVerifying();
        }

        Log::info('MPESA STK PUSH SENDING', array_merge($payload, ['Password' => 'REDACTED']));

        $response = $request->post($this->baseUrl . '/mpesa/stkpush/v1/processrequest', $payload);

        if (!$response->successful()) {
            Log::error('MPESA STK PUSH FAILED', [
                'status' => $response->status(),
                'response' => $response->json() ?? $response->body(),
                'token_used' => substr($token, 0, 8) . '...'
            ]);

            throw new \Exception('STK Push failed: ' . ($response->json('errorMessage') ?? $response->body()));
        }

        Log::info('MPESA STK PUSH RESPONSE', $response->json());

        return $response->json();
    }
}
