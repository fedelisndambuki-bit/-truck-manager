<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FlutterwaveService
{
    protected ?string $secretKey;
    protected ?string $publicKey;
    protected string $baseUrl = 'https://api.flutterwave.com/v3';

    public function __construct()
    {
        $this->secretKey = config('services.flutterwave.secret_key');
        $this->publicKey = config('services.flutterwave.public_key');
    }

    /**
     * Initialize a payment session.
     */
    public function initializePayment(array $data)
    {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/payments", [
                'tx_ref' => $data['tx_ref'],
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'KES',
                'redirect_url' => $data['redirect_url'],
                'customer' => $data['customer'],
                'customizations' => [
                    'title' => $data['title'] ?? 'Tanaki Hub Wallet Funding',
                    'description' => $data['description'] ?? 'Wallet Funding',
                    'logo' => $data['logo'] ?? null,
                ],
                'payment_options' => $data['payment_options'] ?? 'card,mobilemoney,ussd',
            ]);

        if (!$response->successful()) {
            Log::error('Flutterwave Payment Initialization Failed', [
                'payload' => $data,
                'response' => $response->json(),
            ]);
            throw new Exception("Flutterwave Error: " . ($response->json('message') ?? 'Unknown error'));
        }

        return $response->json('data.link');
    }

    /**
     * Verify a transaction with Flutterwave API.
     */
    public function verifyTransaction(string $transactionId)
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transactions/{$transactionId}/verify");

        if (!$response->successful()) {
            Log::error('Flutterwave Transaction Verification Failed', [
                'transaction_id' => $transactionId,
                'response' => $response->json(),
            ]);
            return false;
        }

        return $response->json('data');
    }

    /**
     * Initiate a transfer (Payout).
     */
    public function initiateTransfer(array $data)
    {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transfers", $data);

        if (!$response->successful()) {
            Log::error('Flutterwave Transfer Initiation Failed', [
                'payload' => $data,
                'response' => $response->json(),
            ]);
            throw new Exception("Flutterwave Transfer Error: " . ($response->json('message') ?? 'Unknown error'));
        }

        return $response->json('data');
    }
}
