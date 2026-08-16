<?php

namespace Tests\Feature\Ai;

use App\Models\Company;
use App\Models\Truck;
use App\Services\Ai\DataProviders\FleetAiDataProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fleet_provider_never_returns_another_companys_trucks(): void
    {
        $companyA = Company::factory()->create(['industry' => 'trucking']);
        $companyB = Company::factory()->create(['industry' => 'trucking']);

        Truck::factory()->create(['company_id' => $companyA->id, 'plate_number' => 'KAA 111A']);
        Truck::factory()->create(['company_id' => $companyB->id, 'plate_number' => 'KBB 222B']);

        $provider = app(FleetAiDataProvider::class);

        $summaryA = $provider->summary($companyA->id);
        $summaryB = $provider->summary($companyB->id);

        $platesA = array_column($summaryA['truck_driver_roster'], 'plate');
        $platesB = array_column($summaryB['truck_driver_roster'], 'plate');

        $this->assertContains('KAA 111A', $platesA);
        $this->assertNotContains('KBB 222B', $platesA);

        $this->assertContains('KBB 222B', $platesB);
        $this->assertNotContains('KAA 111A', $platesB);

        $this->assertSame(1, $summaryA['total_trucks']);
        $this->assertSame(1, $summaryB['total_trucks']);
    }

    public function test_tool_call_dispatch_is_scoped_to_the_given_company_id(): void
    {
        $companyA = Company::factory()->create(['industry' => 'trucking']);
        $companyB = Company::factory()->create(['industry' => 'trucking']);

        Truck::factory()->create(['company_id' => $companyA->id, 'plate_number' => 'KAA 111A']);

        $provider = app(FleetAiDataProvider::class);

        // The truck exists, but not under companyB — a tool call scoped to
        // companyB must never find it, even by exact plate match.
        $resultA = $provider->callTool('get_vehicle_details', ['plate' => 'KAA 111A'], $companyA->id);
        $resultB = $provider->callTool('get_vehicle_details', ['plate' => 'KAA 111A'], $companyB->id);

        $this->assertArrayNotHasKey('error', $resultA);
        $this->assertSame('KAA 111A', $resultA['plate']);

        $this->assertArrayHasKey('error', $resultB);
    }

    public function test_registry_authorized_providers_never_crosses_industries(): void
    {
        $registry = app(\App\Services\Ai\AiDataRegistry::class);

        $truckingCompany = Company::factory()->create(['industry' => 'trucking']);
        $user = \App\Models\User::factory()->create(['company_id' => $truckingCompany->id]);
        $user->assignRole(\Spatie\Permission\Models\Role::create(['name' => 'Company Admin', 'guard_name' => 'web']));

        $authorized = $registry->authorizedProviders($user, 'trucking');

        $this->assertArrayHasKey('fleet', $authorized);
        $this->assertArrayNotHasKey('construction', $authorized);
        $this->assertArrayNotHasKey('quarry', $authorized);
    }
}
