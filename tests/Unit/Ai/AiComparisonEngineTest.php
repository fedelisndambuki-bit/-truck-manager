<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\AiComparisonEngine;
use Tests\TestCase;

class AiComparisonEngineTest extends TestCase
{
    public function test_compare_computes_the_standard_shape_for_an_increase(): void
    {
        $result = (new AiComparisonEngine())->compare('fuel_cost', 1180000, 970000, 'KES', 'this month', 'last month');

        $this->assertSame('fuel_cost', $result['metric']);
        $this->assertSame(1180000.0, $result['current_value']);
        $this->assertSame(970000.0, $result['previous_value']);
        $this->assertSame(210000.0, $result['absolute_change']);
        $this->assertSame(21.65, $result['percentage_change']);
        $this->assertSame('increase', $result['direction']);
        $this->assertSame('increase', $result['trend']);
        $this->assertSame('KES', $result['unit']);
        $this->assertSame('this month', $result['period']);
        $this->assertSame('last month', $result['previous_period']);
    }

    public function test_compare_detects_a_decrease(): void
    {
        $result = (new AiComparisonEngine())->compare('expenses', 800, 1000);

        $this->assertSame('decrease', $result['direction']);
        $this->assertSame(-200.0, $result['absolute_change']);
        $this->assertSame(-20.0, $result['percentage_change']);
    }

    public function test_compare_detects_unchanged(): void
    {
        $result = (new AiComparisonEngine())->compare('revenue', 500, 500);

        $this->assertSame('unchanged', $result['direction']);
        $this->assertSame(0.0, $result['absolute_change']);
        $this->assertSame(0.0, $result['percentage_change']);
    }

    public function test_compare_returns_null_percentage_when_previous_is_zero(): void
    {
        // Never fabricate a percentage against a zero/nonexistent baseline
        // (e.g. no data existed in the previous period yet).
        $result = (new AiComparisonEngine())->compare('new_metric', 5000, 0);

        $this->assertNull($result['percentage_change']);
        $this->assertSame('increase', $result['direction']);
        $this->assertSame(5000.0, $result['absolute_change']);
    }
}
