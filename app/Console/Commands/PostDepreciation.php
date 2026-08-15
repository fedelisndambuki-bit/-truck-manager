<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\DepreciationSchedule;
use App\Services\DepreciationService;
use Illuminate\Console\Command;

class PostDepreciation extends Command
{
    protected $signature = 'eam:post-depreciation';

    protected $description = 'Post this period\'s depreciation for every active equipment depreciation schedule';

    public function handle(DepreciationService $service): int
    {
        $periodEnd = now()->endOfMonth();
        $totalPosted = 0;

        Company::where('industry', 'quarry')->each(function (Company $company) use ($service, $periodEnd, &$totalPosted) {
            DepreciationSchedule::withoutGlobalScope('company')
                ->where('company_id', $company->id)
                ->where('status', 'Active')
                ->each(function (DepreciationSchedule $schedule) use ($service, $periodEnd, &$totalPosted) {
                    if ($service->postPeriod($schedule, $periodEnd)) {
                        $totalPosted++;
                    }
                });
        });

        $this->info("Posted depreciation for {$totalPosted} schedule(s) for period ending {$periodEnd->format('Y-m-d')}.");

        return self::SUCCESS;
    }
}
