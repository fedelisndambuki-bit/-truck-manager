<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Company;
use App\Models\Wallet;
use App\Models\WalletFunding;
use App\Services\FlutterwaveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Mockery\MockInterface;

class WalletFundingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup company and user
        $this->company = Company::create(['id' => (string) \Illuminate\Support\Str::uuid()]);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@test.com'
        ]);
        
        // Initialize wallets
        $this->revenueWallet = Wallet::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'company_id' => $this->company->id,
            'type' => 'revenue',
            'available_balance' => 0,
            'ledger_balance' => 0,
            'version' => 1
        ]);
    }

    public function test_can_initialize_funding()
    {
        $this->actingAs($this->user);

        $this->mock(FlutterwaveService::class, function (MockInterface $mock) {
            $mock->shouldReceive('initializePayment')->once()->andReturn('https://checkout.flutterwave.com/pay/abc');
        });

        $service = app(\App\Services\WalletFundingService::class);
        $link = $service->initialize(
            $this->company->id,
            $this->revenueWallet->id,
            100000, // 1000.00 KES
            'KES',
            $this->user->id
        );

        $this->assertEquals('https://checkout.flutterwave.com/pay/abc', $link);
        $this->assertDatabaseHas('fin_wallet_fundings', [
            'company_id' => $this->company->id,
            'amount' => 100000,
            'status' => 'pending'
        ]);
    }

    public function test_can_complete_funding_via_verification()
    {
        $funding = WalletFunding::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'company_id' => $this->company->id,
            'wallet_id' => $this->revenueWallet->id,
            'amount' => 100000,
            'currency' => 'KES',
            'status' => 'pending',
            'flutterwave_tx_ref' => 'FUND-TEST-123',
            'funded_by_user_id' => $this->user->id,
        ]);

        $this->mock(FlutterwaveService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verifyTransaction')->once()->andReturn([
                'status' => 'successful',
                'amount' => 1000.00,
                'currency' => 'KES',
                'payment_type' => 'card',
                'id' => 123456
            ]);
        });

        $service = app(\App\Services\WalletFundingService::class);
        $service->complete('FUND-TEST-123', '123456');

        $this->assertDatabaseHas('fin_wallet_fundings', [
            'id' => $funding->id,
            'status' => 'successful'
        ]);

        $this->revenueWallet->refresh();
        $this->assertEquals(100000, $this->revenueWallet->available_balance);

        $this->assertDatabaseHas('fin_ledger_entries', [
            'reference_id' => $funding->id,
            'debit' => 100000
        ]);
    }

    public function test_webhook_handles_funding()
    {
        $funding = WalletFunding::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'company_id' => $this->company->id,
            'wallet_id' => $this->revenueWallet->id,
            'amount' => 100000,
            'currency' => 'KES',
            'status' => 'pending',
            'flutterwave_tx_ref' => 'FUND-WEBHOOK-123',
            'funded_by_user_id' => $this->user->id,
        ]);

        $this->mock(FlutterwaveService::class, function (MockInterface $mock) {
            $mock->shouldReceive('verifyTransaction')->once()->andReturn([
                'status' => 'successful',
                'amount' => 1000.00,
                'currency' => 'KES',
                'payment_type' => 'mpesa',
                'id' => 789012
            ]);
        });

        $response = $this->postJson('/api/webhooks/flutterwave', [
            'event' => 'charge.completed',
            'data' => [
                'id' => 789012,
                'tx_ref' => 'FUND-WEBHOOK-123',
                'status' => 'successful',
                'amount' => 1000.00,
                'currency' => 'KES'
            ]
        ], [
            'verif-hash' => config('services.flutterwave.webhook_hash')
        ]);

        $response->assertStatus(200);
        
        $funding->refresh();
        $this->assertEquals('successful', $funding->status);
    }
}
