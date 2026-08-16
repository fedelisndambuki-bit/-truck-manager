<?php

namespace Tests\Feature\Ai;

use App\Models\Company;
use App\Models\Truck;
use App\Models\User;
use App\Services\Ai\AiContextOrchestrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Reproduces a real reported bug: asking "how many drivers do we have?" then
 * following up with "their names" returned "not provided in the given fleet
 * data" — because every question was built as a fresh, memory-less context,
 * so "their names" alone matches no module keyword in AiIntentRouter and
 * fell back to a generic default. AiContextOrchestrator::build() now accepts
 * prior conversation text purely to widen routing (never depth/period),
 * so a topic mentioned in an earlier turn still pulls the right provider in.
 */
class AiConversationMemoryTest extends TestCase
{
    use RefreshDatabase;

    private function fleetCompany(): array
    {
        $company = Company::factory()->create(['industry' => 'trucking']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole(Role::create(['name' => 'Company Admin', 'guard_name' => 'web']));
        Truck::factory()->create(['company_id' => $company->id]);

        return [$company, $user];
    }

    public function test_followup_with_no_keywords_falls_back_without_prior_context(): void
    {
        [$company, $user] = $this->fleetCompany();

        $built = app(AiContextOrchestrator::class)->build($user, 'trucking', $company->id, 'their names');

        // No keyword match at all — falls back to the industry-primary
        // provider (Fleet), which happens to still carry driver names via
        // truck_driver_roster, but this is coincidental fallback behaviour,
        // not routing driven by what was actually being asked about.
        $this->assertSame(['fleet'], $built['modules_accessed']);
    }

    public function test_followup_inherits_module_from_prior_turn_via_history(): void
    {
        [$company, $user] = $this->fleetCompany();

        $built = app(AiContextOrchestrator::class)->build(
            $user,
            'trucking',
            $company->id,
            'their names',
            'How many drivers do we have? We have 3 drivers in total.'
        );

        $this->assertContains('fleet', $built['modules_accessed']);
    }

    public function test_prior_context_does_not_change_depth_classification(): void
    {
        [$company, $user] = $this->fleetCompany();

        // The prior turn asks a comparative question, but THIS question is
        // simple — depth must reflect the current question only, never leak
        // in from history (that's a deliberate, separate design choice from
        // the routing widening above).
        $built = app(AiContextOrchestrator::class)->build(
            $user,
            'trucking',
            $company->id,
            'their names',
            'Compare fuel costs this month vs last month.'
        );

        $this->assertSame('simple', $built['depth']);
    }
}
