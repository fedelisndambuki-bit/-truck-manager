<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerQualityRequirement extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'particle_size_distribution' => 'array',
    ];

    public const STATUSES = ['Active', 'Inactive'];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (CustomerQualityRequirement $requirement) {
            if (empty($requirement->status)) {
                $requirement->status = 'Active';
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'customer_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Real threshold comparison against a sample's measured values (keyed
     * by the same names used elsewhere: dust_percent, strength,
     * moisture_percent) — no fabricated scoring. Returns a pass/fail
     * reason array, empty when everything checked passes.
     */
    public function validateMeasuredValues(array $measured): array
    {
        $failures = [];

        if ($this->max_dust_percent !== null && isset($measured['dust_percent']) && (float) $measured['dust_percent'] > (float) $this->max_dust_percent) {
            $failures[] = "Dust {$measured['dust_percent']}% exceeds maximum {$this->max_dust_percent}%";
        }

        if ($this->min_strength !== null && isset($measured['strength']) && (float) $measured['strength'] < (float) $this->min_strength) {
            $failures[] = "Strength {$measured['strength']} below minimum {$this->min_strength}";
        }

        if ($this->moisture_limit !== null && isset($measured['moisture_percent']) && (float) $measured['moisture_percent'] > (float) $this->moisture_limit) {
            $failures[] = "Moisture {$measured['moisture_percent']}% exceeds limit {$this->moisture_limit}%";
        }

        return $failures;
    }
}
