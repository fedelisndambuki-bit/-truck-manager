<?php

namespace Tests\Unit;

use App\Jobs\ProcessAIRequest;
use App\Services\AIService;
use Illuminate\Support\Facades\Cache;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery;
use Tests\TestCase;

class ProcessAIRequestTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_handle_lets_exceptions_bubble(): void
    {
        $job = new ProcessAIRequest(10, 20, 'Question', 'job-id');

        $service = Mockery::mock(AIService::class);
        $service->shouldReceive('processRequest')
            ->once()
            ->andThrow(new \RuntimeException('boom'));

        $this->expectException(\RuntimeException::class);
        $job->handle($service);
    }

    public function test_failed_caches_error_with_composite_key(): void
    {
        $job = new ProcessAIRequest(10, 20, 'Question', 'job-id');

        $job->failed(new \RuntimeException('boom'));

        $payload = Cache::get('ai_response_10_20_job-id');
        $this->assertFalse($payload['success']);
        $this->assertTrue($payload['job_failed']);
    }
}
