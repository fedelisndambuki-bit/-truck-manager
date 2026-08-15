<?php

namespace App\Events;

use App\Models\WalletFunding;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FundingFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $funding;
    public $error;

    public function __construct(WalletFunding $funding, $error = null)
    {
        $this->funding = $funding;
        $this->error = $error;
    }
}
