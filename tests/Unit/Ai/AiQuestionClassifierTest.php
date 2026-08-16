<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\AiQuestionClassifier;
use Tests\TestCase;

class AiQuestionClassifierTest extends TestCase
{
    private function classifier(): AiQuestionClassifier
    {
        return new AiQuestionClassifier();
    }

    public function test_simple_factual_question(): void
    {
        $this->assertSame('simple', $this->classifier()->classify('What is the total fuel cost?'));
    }

    public function test_diagnostic_why_question(): void
    {
        $this->assertSame('diagnostic', $this->classifier()->classify('Why did fuel costs increase?'));
        $this->assertSame('diagnostic', $this->classifier()->classify('Why is Truck 14 performing poorly?'));
    }

    public function test_comparative_question(): void
    {
        $this->assertSame('comparative', $this->classifier()->classify('Compare this month to last month.'));
    }

    public function test_diagnostic_takes_priority_over_analytical_keywords(): void
    {
        // Contains "increase" (analytical trigger) but "why" should win.
        $this->assertSame('diagnostic', $this->classifier()->classify('Why did operating costs increase?'));
    }

    public function test_executive_overview_question(): void
    {
        $this->assertSame('executive', $this->classifier()->classify('How is the business performing?'));
    }

    public function test_analytical_ranking_question(): void
    {
        $this->assertSame('analytical', $this->classifier()->classify('Which trucks have the worst fuel efficiency?'));
    }
}
