<?php

namespace App\Models;

use App\IntegrationEngine\Casts\DeviceCategoryCast;
use App\IntegrationEngine\Casts\ProtocolTypeCast;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tanaki\IntegrationContracts\Enum\ConnectionMedium;
use Tanaki\IntegrationContracts\Enum\DeviceStatus;

/**
 * The per-tenant device inventory — which physical devices a company
 * actually owns and how to reach each one. Distinct from the code-level
 * driver registry (which vendor driver CLASSES exist): this is business
 * data about which of those drivers a given company has installed, with
 * its own connection details. Lives in App\Models (not
 * App\IntegrationEngine) because it's tenant-owned data like every other
 * model here, not an engine-level contract.
 */
class IntegratedDevice extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'connection_config' => 'array',
        'category' => DeviceCategoryCast::class,
        'connection_medium' => ConnectionMedium::class,
        'protocol' => ProtocolTypeCast::class,
        'status' => DeviceStatus::class,
        'last_seen_at' => 'datetime',
        'last_health_check_at' => 'datetime',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
