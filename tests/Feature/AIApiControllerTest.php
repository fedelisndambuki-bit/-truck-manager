<?php

namespace Tests\Feature;

use App\Http\Controllers\API\AIController;
use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AIApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_ask_requires_company(): void
    {
        $role = Role::create([
            'name' => 'Company Admin',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole($role);

        Auth::login($user);

        $controller = app(AIController::class);
        $request = Request::create('/api/ai/ask', 'POST', [
            'question' => 'How is my fleet today?'
        ]);

        $response = $controller->ask($request);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('Company association is required to use the AI assistant.', $response->getData(true)['message']);
    }

    public function test_api_get_result_is_scoped_to_company_and_user(): void
    {
        $role = Role::create([
            'name' => 'Company Admin',
            'guard_name' => 'web',
        ]);

        $company = Company::create();
        $otherCompany = Company::create();

        $userA = User::factory()->create(['company_id' => $company->id]);
        $userA->assignRole($role);

        $userB = User::factory()->create(['company_id' => $otherCompany->id]);
        $userB->assignRole($role);

        $jobId = (string) Str::uuid();
        Cache::put("ai_response_{$company->id}_{$userA->id}_{$jobId}", [
            'success' => true,
            'response' => 'ok'
        ], now()->addMinutes(5));

        $controller = app(AIController::class);

        Auth::login($userB);
        $response = $controller->getResult(Request::create('/api/ai/result', 'GET', [
            'job_id' => $jobId,
        ]));
        $this->assertSame(404, $response->getStatusCode());

        Auth::login($userA);
        $response = $controller->getResult(Request::create('/api/ai/result', 'GET', [
            'job_id' => $jobId,
        ]));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertNull(Cache::get("ai_response_{$company->id}_{$userA->id}_{$jobId}"));
    }

    public function test_access_ai_gate_denies_without_role(): void
    {
        $company = Company::create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->expectException(AuthorizationException::class);
        Gate::forUser($user)->authorize('accessAI');
    }
}
