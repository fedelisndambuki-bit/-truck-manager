<?php

namespace App\Events;

use App\Models\WalletFunding;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletFunded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $funding;

    public function __construct(WalletFunding $funding)
    {
        $this->funding = $funding;
    }
}
