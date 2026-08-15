<?php

namespace App\Models;

use App\Traits\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeighbridgeException extends BaseModel
{
    use HasApprovalWorkflow;

    protected $guarded = [];

    public const STATUSES = ['Draft', 'Submitted', 'Approved', 'Rejected'];

    public const EXCEPTION_TYPES = [
        'Overloaded', 'Underloaded', 'Wrong Product', 'Wrong Stockpile', 'Wrong Customer', 'Wrong Quantity',
        'Quality Failure', 'Vehicle Breakdown', 'Scale Failure', 'Power Failure', 'Network Failure',
        'Duplicate Ticket', 'Cancelled Dispatch', 'Manual Override', 'Unauthorized Entry',
        'Expired Driver License', 'Expired Vehicle Inspection', 'Missing Reservation', 'Negative Stock',
        // Delivery-journey exceptions (Dispatch Management)
        'Delayed Dispatch', 'Customer Not Available', 'Traffic Delay', 'Weather Delay',
        'Accident', 'Vehicle Diversion', 'Failed Delivery',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (WeighbridgeException $exception) {
            if (empty($exception->exception_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $exception->exception_number = 'EXC-' . str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function dispatchOrder(): BelongsTo
    {
        return $this->belongsTo(DispatchOrder::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reported_by_id');
    }
}
