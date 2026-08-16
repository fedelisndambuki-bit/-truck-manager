<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\AiPeriodParser;
use Tests\TestCase;

class AiPeriodParserTest extends TestCase
{
    public function test_today(): void
    {
        $range = (new AiPeriodParser())->parse('What happened today?');

        $this->assertSame('today', $range['label']);
        $this->assertTrue($range['from']->isToday());
        $this->assertTrue($range['to']->isToday());
    }

    public function test_yesterday(): void
    {
        $range = (new AiPeriodParser())->parse('How did we do yesterday?');

        $this->assertSame('yesterday', $range['label']);
        $this->assertTrue($range['from']->isYesterday());
    }

    public function test_this_month(): void
    {
        $range = (new AiPeriodParser())->parse('Fuel cost this month');

        $this->assertSame('this month', $range['label']);
        $this->assertSame(now()->startOfMonth()->toDateString(), $range['from']->toDateString());
    }

    public function test_last_month(): void
    {
        $range = (new AiPeriodParser())->parse('Fuel cost last month');

        $this->assertSame('last month', $range['label']);
        $this->assertSame(now()->subMonthNoOverflow()->startOfMonth()->toDateString(), $range['from']->toDateString());
        $this->assertSame(now()->subMonthNoOverflow()->endOfMonth()->toDateString(), $range['to']->toDateString());
    }

    public function test_this_quarter_and_last_quarter_are_distinct_and_bounded(): void
    {
        $thisQuarter = (new AiPeriodParser())->parse('production this quarter');
        $lastQuarter = (new AiPeriodParser())->parse('production last quarter');

        $this->assertSame('this quarter', $thisQuarter['label']);
        $this->assertSame('last quarter', $lastQuarter['label']);
        $this->assertTrue($lastQuarter['to']->lessThan($thisQuarter['from']));
    }

    public function test_this_year_and_last_year(): void
    {
        $thisYear = (new AiPeriodParser())->parse('revenue this year');
        $lastYear = (new AiPeriodParser())->parse('revenue last year');

        $this->assertSame(now()->year, $thisYear['from']->year);
        $this->assertSame(now()->year - 1, $lastYear['from']->year);
    }

    public function test_custom_date_range(): void
    {
        $range = (new AiPeriodParser())->parse('spend from 2026-01-01 to 2026-01-31');

        $this->assertSame('2026-01-01', $range['from']->toDateString());
        $this->assertSame('2026-01-31', $range['to']->toDateString());
    }

    public function test_unmatched_question_defaults_to_this_month(): void
    {
        $range = (new AiPeriodParser())->parse('What is the meaning of life?');

        $this->assertSame('this month', $range['label']);
    }
}
