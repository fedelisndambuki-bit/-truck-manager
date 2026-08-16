<?php

namespace Tests\Feature\Ai;

use App\Models\AiLog;
use App\Models\AiUsageLog;
use App\Models\Company;
use App\Models\User;
use App\Services\Ai\AiDataRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AiDataProviderAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_finance_permission_does_not_get_finance_provider(): void
    {
        Permission::create(['name' => 'finance.chart_of_accounts', 'guard_name' => 'web']);
        // A role name deliberately NOT in AppServiceProvider's Gate::before
        // bypass list (Super Admin/Company Admin/Fleet Manager/Accountant),
        // so this test actually exercises the permission check rather than
        // being silently waved through.
        $role = Role::create(['name' => 'Restricted Test Role', 'guard_name' => 'web']);

        $company = Company::factory()->create(['industry' => 'trucking']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole($role);

        $registry = app(AiDataRegistry::class);
        $authorized = $registry->authorizedProviders($user, 'trucking');

        $this->assertArrayNotHasKey('finance', $authorized);
    }

    public function test_user_with_finance_permission_does_get_finance_provider(): void
    {
        Permission::create(['name' => 'finance.chart_of_accounts', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Restricted Test Role', 'guard_name' => 'web']);
        $role->givePermissionTo('finance.chart_of_accounts');

        $company = Company::factory()->create(['industry' => 'trucking']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole($role);

        $registry = app(AiDataRegistry::class);
        $authorized = $registry->authorizedProviders($user, 'trucking');

        $this->assertArrayHasKey('finance', $authorized);
    }

    public function test_process_request_denies_finance_question_without_permission_and_makes_no_ai_call(): void
    {
        Permission::create(['name' => 'finance.chart_of_accounts', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Restricted Test Role', 'guard_name' => 'web']);

        $company = Company::factory()->create(['industry' => 'trucking']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole($role);

        $ai = app(\App\Services\AIService::class);
        $result = $ai->processRequest($company->id, $user->id, 'What is our total revenue and unpaid invoices?');

        $this->assertTrue($result['success']);
        $this->assertStringContainsString("don't have permission", $result['response']);
        $this->assertStringContainsString('Finance', $result['response']);

        // No AiUsageLog row — a denial must never spend a real AI call/tokens.
        $this->assertSame(0, AiUsageLog::where('company_id', $company->id)->count());

        $log = AiLog::where('company_id', $company->id)->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame([], $log->modules_accessed);
    }

    public function test_unauthorized_tool_call_is_refused_even_if_provider_exists_in_registry(): void
    {
        $company = Company::factory()->create(['industry' => 'trucking']);
        $registry = app(AiDataRegistry::class);

        // Simulate a tool name belonging to a provider that is NOT in the
        // caller's authorized set (e.g. Finance, denied by permission).
        $result = $registry->callTool('get_financial_summary', [], $company->id, []);

        $this->assertArrayHasKey('error', $result);
    }
}
