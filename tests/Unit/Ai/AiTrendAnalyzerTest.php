<?php

namespace Tests\Unit\Ai;

use App\Services\Ai\AiTrendAnalyzer;
use Tests\TestCase;

class AiTrendAnalyzerTest extends TestCase
{
    public function test_analyze_handles_empty_series(): void
    {
        $result = (new AiTrendAnalyzer())->analyze([]);

        $this->assertSame('unknown', $result['direction']);
        $this->assertNull($result['average']);
        $this->assertSame([], $result['notable_changes']);
    }

    public function test_analyze_detects_increasing_trend(): void
    {
        $series = ['Mon' => 100, 'Tue' => 105, 'Wed' => 110, 'Thu' => 150, 'Fri' => 160, 'Sat' => 165, 'Sun' => 170];

        $result = (new AiTrendAnalyzer())->analyze($series);

        $this->assertSame('increasing', $result['direction']);
        $this->assertSame('Sun', $result['highest']['label']);
        $this->assertSame('Mon', $result['lowest']['label']);
    }

    public function test_analyze_detects_decreasing_trend(): void
    {
        $series = ['Mon' => 200, 'Tue' => 180, 'Wed' => 150, 'Thu' => 100, 'Fri' => 90];

        $result = (new AiTrendAnalyzer())->analyze($series);

        $this->assertSame('decreasing', $result['direction']);
    }

    public function test_analyze_flags_a_spike_above_the_threshold(): void
    {
        $series = ['Mon' => 100, 'Tue' => 105, 'Wed' => 98, 'Thu' => 250, 'Fri' => 110];

        $result = (new AiTrendAnalyzer())->analyze($series, 30);

        $spikes = array_filter($result['notable_changes'], fn ($c) => $c['type'] === 'spike');
        $this->assertNotEmpty($spikes);
        $this->assertSame('Thu', array_values($spikes)[0]['label']);
    }

    public function test_analyze_flags_a_drop_above_the_threshold(): void
    {
        $series = ['Mon' => 100, 'Tue' => 105, 'Wed' => 98, 'Thu' => 10, 'Fri' => 110];

        $result = (new AiTrendAnalyzer())->analyze($series, 30);

        $drops = array_filter($result['notable_changes'], fn ($c) => $c['type'] === 'drop');
        $this->assertNotEmpty($drops);
        $this->assertSame('Thu', array_values($drops)[0]['label']);
    }

    public function test_analyze_treats_flat_series_as_flat(): void
    {
        $series = ['Mon' => 100, 'Tue' => 100, 'Wed' => 100, 'Thu' => 100];

        $result = (new AiTrendAnalyzer())->analyze($series);

        $this->assertSame('flat', $result['direction']);
        $this->assertSame([], $result['notable_changes']);
    }
}
