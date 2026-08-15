<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AIAssistantWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_web_requires_company(): void
    {
        Permission::create([
            'name' => 'use ai assistant',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create(['company_id' => null]);
        $user->givePermissionTo('use ai assistant');

        $response = $this->actingAs($user)->postJson('/ai/ask', [
            'message' => 'Hello AI',
            'module' => 'dashboard',
        ]);

        $response->assertStatus(422)->assertJson([
            'message' => 'Company association is required to use the AI assistant.'
        ]);
    }
}
