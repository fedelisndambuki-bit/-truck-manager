<?php

namespace App\Modules\Ledger;

use Illuminate\Database\Eloquent\Model;
use App\Traits\MultiTenantScope;

class LedgerEntry extends Model
{
    use MultiTenantScope;

    protected $guarded = [];
}
