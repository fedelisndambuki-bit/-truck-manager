<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;
use App\Models\Company;
use Illuminate\Support\Str;

class InitializeFinancials extends Command
{
    protected $signature = 'financials:init';
    protected $description = 'Initialize financial wallets for existing companies';

    public function handle()
    {
        $companies = Company::all();
        
        foreach ($companies as $company) {
            foreach (['revenue', 'expense', 'driver_pool'] as $type) {
                Wallet::updateOrCreate(
                    ['company_id' => $company->id, 'type' => $type],
                    [
                        'id' => Str::uuid(),
                        'available_balance' => 0,
                        'ledger_balance' => 0,
                        'version' => 1
                    ]
                );
            }
        }

        $this->info('Financial wallets initialized for ' . $companies->count() . ' companies.');
    }
}
