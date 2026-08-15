<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\PreventiveMaintenanceSchedulingService;
use Illuminate\Console\Command;

class GeneratePreventiveMaintenanceWorkOrders extends Command
{
    protected $signature = 'eam:generate-pm-work-orders';

    protected $description = 'Generate maintenance work orders for preventive maintenance plans that are due';

    public function handle(PreventiveMaintenanceSchedulingService $service): int
    {
        $totalCreated = 0;

        Company::where('industry', 'quarry')->each(function (Company $company) use ($service, &$totalCreated) {
            $created = $service->generateDueWorkOrders($company->id);
            $totalCreated += $created->count();

            if ($created->isNotEmpty()) {
                $this->info("{$company->name}: generated {$created->count()} preventive maintenance work order(s).");
            }
        });

        $this->info("Done. {$totalCreated} work order(s) generated.");

        return self::SUCCESS;
    }
}
