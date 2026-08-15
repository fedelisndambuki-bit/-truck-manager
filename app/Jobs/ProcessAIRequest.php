<?php

namespace App\Jobs;

use App\Services\AIService;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAIRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $retryAfter = 5;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $companyId,
        private int $userId,
        private string $question,
        private string $jobId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AIService $aiService): void
    {
        $result = $aiService->processRequest($this->companyId, $this->userId, $this->question);

        // Store result in cache for frontend to retrieve
        cache()->put(
            "ai_response_{$this->companyId}_{$this->userId}_{$this->jobId}",
            $result,
            now()->addMinutes(5)
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessAIRequest Job Failed Permanently', [
            'company_id' => $this->companyId,
            'user_id' => $this->userId,
            'question' => $this->question,
            'job_id' => $this->jobId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Store final error in cache
        cache()->put(
            "ai_response_{$this->companyId}_{$this->userId}_{$this->jobId}",
            [
                'success' => false,
                'error' => 'AI service is currently unavailable. Please contact support.',
                'job_failed' => true
            ],
            now()->addMinutes(5)
        );
    }

    /**
     * Get the unique job identifier.
     */
    public function uniqueId(): string
    {
        return $this->jobId;
    }
}
