<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabTest extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'measured_values' => 'array',
        'expected_values' => 'array',
        'tolerance' => 'decimal:4',
    ];

    public const STATUSES = ['Pending', 'In Progress', 'Completed'];

    public const RESULTS = ['Pass', 'Fail', 'Conditional'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (LabTest $test) {
            if (empty($test->test_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $test->test_number = 'TST-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            if (empty($test->status)) {
                $test->status = 'Pending';
            }
        });
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(LabSample::class, 'lab_sample_id');
    }

    public function testMethod(): BelongsTo
    {
        return $this->belongsTo(TestMethod::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'technician_id');
    }

    public function labEquipment(): BelongsTo
    {
        return $this->belongsTo(LabEquipment::class);
    }

    /**
     * Real numeric comparison of each measured_values key against its
     * expected_values counterpart, within tolerance — no fabricated
     * scoring. Non-numeric or missing pairs are skipped (left to the
     * technician's manual result judgement instead).
     */
    public function computeResult(): string
    {
        $measured = $this->measured_values ?? [];
        $expected = $this->expected_values ?? [];
        $tolerance = (float) ($this->tolerance ?? 0);

        $comparisons = [];
        foreach ($expected as $key => $expectedValue) {
            if (! array_key_exists($key, $measured) || ! is_numeric($expectedValue) || ! is_numeric($measured[$key])) {
                continue;
            }

            $comparisons[] = abs((float) $measured[$key] - (float) $expectedValue) <= $tolerance;
        }

        if (empty($comparisons)) {
            return 'Conditional';
        }

        return in_array(false, $comparisons, true) ? 'Fail' : 'Pass';
    }

    public function complete(?string $result = null): void
    {
        $this->update([
            'end_time' => now(),
            'status' => 'Completed',
            'result' => $result ?? $this->computeResult(),
        ]);
    }
}
