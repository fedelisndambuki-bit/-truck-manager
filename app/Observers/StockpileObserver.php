<?php

namespace App\Observers;

use App\Models\SamplingRequest;
use App\Models\Stockpile;

/**
 * Auto-creates a SamplingRequest (trigger 'New Stockpile') whenever a new
 * Stockpile is created — zero edits to Stockpile.php itself.
 */
class StockpileObserver
{
    public function created(Stockpile $stockpile): void
    {
        SamplingRequest::create([
            'company_id' => $stockpile->company_id,
            'trigger' => 'New Stockpile',
            'priority' => 'Normal',
            'due_date' => now()->addDays(2),
            'notes' => "Auto-generated for new stockpile {$stockpile->label} ({$stockpile->stockpile_code})",
        ]);
    }
}
