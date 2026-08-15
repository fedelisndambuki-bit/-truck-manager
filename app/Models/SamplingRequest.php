<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SamplingRequest extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'due_date' => 'date',
    ];

    public const TRIGGERS = [
        'Production Completed', 'New Stockpile', 'Customer Order', 'Dispatch Approval',
        'Routine Inspection', 'Random Inspection', 'Complaint Investigation',
    ];

    public const PRIORITIES = ['Low', 'Normal', 'High', 'Urgent'];

    public const STATUSES = ['Pending', 'Assigned', 'In Progress', 'Completed', 'Cancelled'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (SamplingRequest $request) {
            if (empty($request->request_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $request->request_number = 'SRQ-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if (empty($request->priority)) {
                $request->priority = 'Normal';
            }

            if (empty($request->status)) {
                $request->status = 'Pending';
            }
        });
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'requested_by_id');
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_technician_id');
    }

    /**
     * lab_sample_id is a soft reference (no DB-level FK - lab_samples is
     * created after sampling_requests in migration order, and lab_samples
     * itself holds the hard-constrained reverse FK back to this table).
     */
    public function sample(): BelongsTo
    {
        return $this->belongsTo(LabSample::class, 'lab_sample_id');
    }

    public function fulfill(LabSample $sample): void
    {
        $this->update(['lab_sample_id' => $sample->id, 'status' => 'Completed']);
    }
}
