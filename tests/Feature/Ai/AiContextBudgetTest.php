<?php

namespace Tests\Feature\Ai;

use App\Models\Company;
use App\Models\Truck;
use App\Services\Ai\DataProviders\FleetAiDataProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiContextBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_large_fleet_is_capped_not_dumped_in_full(): void
    {
        config(['ai.max_records_per_provider' => 10]);

        $company = Company::factory()->create(['industry' => 'trucking']);
        Truck::factory()->count(25)->create(['company_id' => $company->id]);

        $summary = app(FleetAiDataProvider::class)->summary($company->id);

        $this->assertSame(25, $summary['total_trucks'], 'the real total count is still reported, not capped');
        $this->assertCount(10, $summary['truck_driver_roster'], 'the detailed roster respects max_records_per_provider');
    }

    public function test_orchestrator_truncates_eager_context_once_char_budget_is_exceeded(): void
    {
        config(['ai.max_context_chars' => 50]);

        $company = Company::factory()->create(['industry' => 'trucking']);
        $user = \App\Models\User::factory()->create(['company_id' => $company->id]);
        $user->assignRole(\Spatie\Permission\Models\Role::create(['name' => 'Company Admin', 'guard_name' => 'web']));

        $orchestrator = app(\App\Services\Ai\AiContextOrchestrator::class);
        $built = $orchestrator->build($user, 'trucking', $company->id, 'Give me an overall business performance summary.');

        $this->assertTrue($built['context_truncated']);
        // Even when eager context is truncated, every authorized provider
        // must still be reachable as a tool for the model to drill into.
        $this->assertNotEmpty($built['tools']);
    }

    public function test_default_config_caps_at_forty_records(): void
    {
        $this->assertSame(40, config('ai.max_records_per_provider'));
    }
}
