<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class PaystackService
{
    protected string $baseUrl;
    protected string $secretKey;

    public function __construct()
    {
        $this->baseUrl   = config('services.paystack.base_url', 'https://api.paystack.co');
        $this->secretKey = config('services.paystack.secret_key', '');
    }

    // ─── Core HTTP helper ─────────────────────────────────────────

    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($this->secretKey)
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->timeout(30)
            ->withOptions(['version' => 1.1]) // PHP OpenSSL hangs on HTTP/2 with Paystack; 1.1 works fine
            ->throw();
    }

    // ─── Transactions ─────────────────────────────────────────────

    /**
     * Initialize a Paystack payment.
     * Amount should be passed in the major currency unit (e.g. KES 1999).
     * This method converts it to the smallest unit (kobo/pesewas ×100).
     *
     * @param  array{
     *   email: string,
     *   amount: int|float,
     *   reference: string,
     *   callback_url: string,
     *   currency?: string,
     *   channels?: array,
     *   metadata?: array,
     * } $data
     * @return string The authorization_url to redirect the user to
     */
    public function initializePayment(array $data): string
    {
        $payload = [
            'email'        => $data['email'],
            'amount'       => (int) round($data['amount'] * 100), // convert to kobo
            'reference'    => $data['reference'],
            'callback_url' => $data['callback_url'],
            'currency'     => $data['currency'] ?? 'KES',
            'channels'     => $data['channels'] ?? ['card', 'mobile_money', 'bank_transfer'],
            'metadata'     => $data['metadata'] ?? [],
        ];

        $response = $this->client()->post('/transaction/initialize', $payload)->json();

        if (! ($response['status'] ?? false)) {
            throw new RuntimeException('Paystack payment initialization failed: ' . ($response['message'] ?? 'Unknown error'));
        }

        return $response['data']['authorization_url'];
    }

    /**
     * Verify a transaction using its reference.
     *
     * @return array The transaction data from Paystack (data key)
     */
    public function verifyTransaction(string $reference): array
    {
        $response = $this->client()->get("/transaction/verify/{$reference}")->json();

        if (! ($response['status'] ?? false)) {
            throw new RuntimeException('Paystack transaction verification failed: ' . ($response['message'] ?? 'Unknown error'));
        }

        return $response['data'];
    }

    /**
     * Charge a previously-saved, reusable card authorization — used for
     * recurring subscription renewals so the customer doesn't need to
     * re-enter their card. Only "reusable" authorizations (returned on a
     * card payment, not mobile money/bank transfer) can be charged this way.
     *
     * Note: Paystack returns HTTP 200 with data.status = 'failed' for a
     * declined renewal charge (it's not an HTTP-level error), so callers
     * must check the returned status themselves rather than relying on a
     * thrown exception.
     *
     * @param  array{
     *   authorization_code: string,
     *   email: string,
     *   amount: int|float,
     *   reference: string,
     * } $data
     * @return array The transaction data from Paystack (data key)
     */
    public function chargeAuthorization(array $data): array
    {
        $payload = [
            'authorization_code' => $data['authorization_code'],
            'email'              => $data['email'],
            'amount'             => (int) round($data['amount'] * 100), // convert to kobo
            'reference'          => $data['reference'],
        ];

        $response = $this->client()->post('/transaction/charge_authorization', $payload)->json();

        if (! ($response['status'] ?? false)) {
            throw new RuntimeException('Paystack charge authorization request failed: ' . ($response['message'] ?? 'Unknown error'));
        }

        return $response['data'];
    }

    // ─── Transfers (Payouts) ──────────────────────────────────────

    /**
     * Create a transfer recipient (required before initiating a transfer).
     *
     * For M-Pesa (Kenya):
     *   type        = 'mobile_money'
     *   currency    = 'KES'
     *   bank_code   = 'MPESA'
     *   account_number = phone in format 2547XXXXXXXX
     *
     * @param  array{
     *   type: string,
     *   name: string,
     *   account_number: string,
     *   bank_code: string,
     *   currency?: string,
     *   description?: string,
     * } $data
     * @return string The recipient_code to use in initiateTransfer()
     */
    public function createTransferRecipient(array $data): string
    {
        $payload = [
            'type'           => $data['type'],
            'name'           => $data['name'],
            'account_number' => $data['account_number'],
            'bank_code'      => $data['bank_code'],
            'currency'       => $data['currency'] ?? 'KES',
        ];

        if (! empty($data['description'])) {
            $payload['description'] = $data['description'];
        }

        $response = $this->client()->post('/transferrecipient', $payload)->json();

        if (! ($response['status'] ?? false)) {
            throw new RuntimeException('Paystack recipient creation failed: ' . ($response['message'] ?? 'Unknown error'));
        }

        return $response['data']['recipient_code'];
    }

    /**
     * Initiate a transfer to a previously created recipient.
     * Amount should be in major currency unit; this method converts to kobo (×100).
     *
     * @param  array{
     *   amount: int|float,
     *   recipient: string,
     *   reference: string,
     *   reason?: string,
     *   currency?: string,
     * } $data
     * @return array The transfer object containing transfer_code, status, etc.
     */
    public function initiateTransfer(array $data): array
    {
        $payload = [
            'source'    => 'balance',
            'amount'    => (int) round($data['amount'] * 100), // convert to kobo
            'recipient' => $data['recipient'],
            'reference' => $data['reference'],
            'reason'    => $data['reason'] ?? 'Payout',
            'currency'  => $data['currency'] ?? 'KES',
        ];

        $response = $this->client()->post('/transfer', $payload)->json();

        if (! ($response['status'] ?? false)) {
            throw new RuntimeException('Paystack transfer initiation failed: ' . ($response['message'] ?? 'Unknown error'));
        }

        return $response['data'];
    }

    // ─── Webhook validation ───────────────────────────────────────

    /**
     * Validate the x-paystack-signature header against the raw request body.
     */
    public function validateWebhookSignature(string $rawBody, string $signature): bool
    {
        $expected = hash_hmac('sha512', $rawBody, $this->secretKey);

        return hash_equals($expected, $signature);
    }
}
