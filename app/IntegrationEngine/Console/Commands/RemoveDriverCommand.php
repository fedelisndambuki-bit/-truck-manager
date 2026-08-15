<?php

namespace App\IntegrationEngine\Console\Commands;

use App\Models\IntegratedDevice;
use Illuminate\Console\Command;

class RemoveDriverCommand extends Command
{
    protected $signature = 'integration-engine:driver:remove {device : IntegratedDevice ID}';

    protected $description = 'Deactivate (or permanently delete) an integrated device';

    public function handle(): int
    {
        $device = IntegratedDevice::withoutGlobalScope('company')->find($this->argument('device'));

        if (! $device) {
            $this->error('Device not found.');

            return self::FAILURE;
        }

        if ($this->confirm("Permanently delete device \"{$device->name}\"? Choose 'no' to just deactivate it instead.", false)) {
            $device->delete();
            $this->info('Device deleted.');
        } else {
            $device->update(['status' => 'inactive']);
            $this->info('Device deactivated.');
        }

        return self::SUCCESS;
    }
}
