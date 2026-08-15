<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settlement extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'settlement_id',
        'truck_id',
        'employee_id',
        'settlement_date',
        'pay_period_start',
        'pay_period_end',
        'trucks',
        'trips',
        'net_amount',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'settlement_date' => 'date',
        'pay_period_start' => 'date',
        'pay_period_end' => 'date',
        'net_amount' => 'decimal:2',
    ];

    /**
     * Define the relationship to the Truck model.
     */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }

    /**
     * Define the relationship to the Employee (Driver) model.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * Scope a query to only include pending settlements.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'PENDING');
    }

    /**
     * Scope a query to only include paid settlements.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'PAID');
    }

    /**
     * Scope a query to only include processing settlements.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'PROCESSING');
    }

    /**
     * Get the formatted pay period.
     */
    public function getPayPeriodAttribute()
    {
        return $this->pay_period_start->format('M d').' - '.$this->pay_period_end->format('M d, Y');
    }

    /**
     * Get the formatted settlement date.
     */
    public function getFormattedDateAttribute()
    {
        return $this->settlement_date->format('M d, Y');
    }
}
