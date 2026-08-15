<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\MultiTenantScope;

abstract class BaseFinancialModel extends Model
{
    use MultiTenantScope;

    // Default to the main connection (SQLite) to ensure it works out of the box
    // User can switch back to 'financial' (PostgreSQL) when the driver is available
    protected $connection = null;
    
    public $incrementing = false;
    protected $keyType = 'string';
}
