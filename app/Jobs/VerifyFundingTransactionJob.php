<?php

namespace App\Jobs;

use App\Models\WalletFunding;
use App\Services\WalletFundingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyFundingTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 600];

    protected string $txRef;
    protected string $transactionId;

    public function __construct(string $txRef, string $transactionId)
    {
        $this->txRef = $txRef;
        $this->transactionId = $transactionId;
    }

    public function handle(WalletFundingService $service): void
    {
        try {
            $service->complete($this->txRef, $this->transactionId);
            Log::info("Asynchronous funding verification successful for Ref: {$this->txRef}");
        } catch (\Exception $e) {
            Log::error("Asynchronous funding verification failed: " . $e->getMessage());
            throw $e;
        }
    }
}
