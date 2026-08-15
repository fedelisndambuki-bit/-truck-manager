<?php

namespace App\Observers;

use App\Models\PartUsage;

class PartUsageObserver
{
    /**
     * Block the insert entirely when there isn't enough stock — reduceStock()
     * used to let stock_quantity go negative with no validation at all.
     */
    public function creating(PartUsage $partUsage): void
    {
        $part = $partUsage->part;

        if ($part && $partUsage->quantity > $part->stock_quantity) {
            throw new \RuntimeException(
                "Insufficient stock for '{$part->name}': requested {$partUsage->quantity}, only {$part->stock_quantity} available."
            );
        }
    }

    /**
     * Handle the PartUsage "created" event.
     */
    public function created(PartUsage $partUsage): void
    {
        $this->reduceStock($partUsage);
    }

    /**
     * Handle the PartUsage "deleted" event.
     */
    public function deleted(PartUsage $partUsage): void
    {
        $this->restoreStock($partUsage);
    }

    protected function reduceStock(PartUsage $partUsage): void
    {
        $part = $partUsage->part;
        if ($part) {
            $part->stock_quantity -= $partUsage->quantity;
            $part->save();
        }
    }

    protected function restoreStock(PartUsage $partUsage): void
    {
        $part = $partUsage->part;
        if ($part) {
            $part->stock_quantity += $partUsage->quantity;
            $part->save();
        }
    }
}
