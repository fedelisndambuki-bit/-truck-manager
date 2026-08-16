<?php

namespace Tests\Feature\Ai;

use App\Services\Ai\AiIntentRouter;
use Tests\TestCase;

class AiIntentRoutingTest extends TestCase
{
    private function router(): AiIntentRouter
    {
        return new AiIntentRouter();
    }

    private function allKeys(): array
    {
        return ['fleet', 'fuel', 'workshop', 'weighbridge', 'quarry', 'construction', 'finance', 'hr', 'procurement', 'driverPerformance', 'crm'];
    }

    public function test_fuel_question_retrieves_fuel_and_fleet(): void
    {
        $keys = $this->router()->route('Which truck consumed the most fuel this month?', $this->allKeys());

        $this->assertContains('fuel', $keys);
        $this->assertContains('fleet', $keys);
        $this->assertNotContains('quarry', $keys);
        $this->assertNotContains('finance', $keys);
    }

    public function test_maintenance_question_retrieves_workshop_fleet_finance_procurement(): void
    {
        $keys = $this->router()->route('How much did we spend on maintenance this quarter?', $this->allKeys());

        $this->assertContains('workshop', $keys);
        $this->assertContains('fleet', $keys);
        $this->assertContains('finance', $keys);
        $this->assertContains('procurement', $keys);
    }

    public function test_weighbridge_question_retrieves_weighbridge_data(): void
    {
        $keys = $this->router()->route('What was our weighbridge tonnage today?', $this->allKeys());

        $this->assertContains('weighbridge', $keys);
    }

    public function test_quarry_production_question_combines_quarry_weighbridge_fleet_fuel(): void
    {
        $keys = $this->router()->route('What was our quarry production last week?', $this->allKeys());

        $this->assertContains('quarry', $keys);
        $this->assertContains('weighbridge', $keys);
        $this->assertContains('fleet', $keys);
        $this->assertContains('fuel', $keys);
    }

    public function test_cross_module_comparison_question_retrieves_both_providers(): void
    {
        $keys = $this->router()->route('Compare quarry production with fuel consumption.', $this->allKeys());

        $this->assertContains('quarry', $keys);
        $this->assertContains('fuel', $keys);
    }

    public function test_overall_business_summary_retrieves_all_authorized_providers(): void
    {
        $available = ['fleet', 'fuel', 'finance', 'crm'];
        $keys = $this->router()->route('Give me an overall business performance summary.', $available);

        $this->assertSame($available, $keys);
    }

    public function test_router_only_returns_keys_that_are_available(): void
    {
        // Even though "maintenance" would normally include 'finance', a caller
        // that didn't pass 'finance' in the available set (e.g. permission
        // already filtered it out) must never get it back.
        $keys = $this->router()->route('maintenance cost this quarter', ['workshop', 'fleet']);

        $this->assertSame(['workshop', 'fleet'], $keys);
        $this->assertNotContains('finance', $keys);
    }
}
