<?php

namespace App\Models;

use App\Traits\MultiTenantScope;
use App\Traits\ProjectAccessScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory, MultiTenantScope, ProjectAccessScope;

    protected $fillable = [
        'company_id',
        'trip_id',
        'project_id',
        'invoice_number',
        'client_id',
        'truck_id',
        'driver_id',
        'route_from',
        'route_to',
        'progress_claim_id',
        'type',
        'attachment_path',
        'invoice_date',
        'due_date',
        'line_items',
        'subtotal',
        'tax',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'line_items' => 'array',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class); // Assuming 'drivers' are 'employees'
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function progressClaim(): BelongsTo
    {
        return $this->belongsTo(ProgressClaim::class);
    }
}
