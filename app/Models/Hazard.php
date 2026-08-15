<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hazard extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'target_date' => 'date',
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function (Hazard $hazard) {
            if (empty($hazard->hazard_number)) {
                $lastId = (int) static::withoutGlobalScope('company')->max('id');
                $hazard->hazard_number = 'HAZ-' . str_pad((string) ($lastId + 1), 5, '0', STR_PAD_LEFT);
            }

            $hazard->risk_rating = self::calculateRiskRating($hazard->likelihood, $hazard->impact);
        });

        static::updating(function (Hazard $hazard) {
            if ($hazard->isDirty(['likelihood', 'impact'])) {
                $hazard->risk_rating = self::calculateRiskRating($hazard->likelihood, $hazard->impact);
            }
        });
    }

    public static function calculateRiskRating(?string $likelihood, ?string $impact): string
    {
        $scale = ['Rare' => 1, 'Unlikely' => 2, 'Possible' => 3, 'Likely' => 4, 'Almost Certain' => 5,
            'Negligible' => 1, 'Minor' => 2, 'Moderate' => 3, 'Major' => 4, 'Catastrophic' => 5];

        $score = ($scale[$likelihood] ?? 3) * ($scale[$impact] ?? 3);

        return match (true) {
            $score >= 15 => 'Critical',
            $score >= 8 => 'High',
            $score >= 4 => 'Medium',
            default => 'Low',
        };
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function responsibleStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'responsible_staff_id');
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class);
    }

    public const TYPES = [
        'Fall From Height', 'Slip / Trip Hazard', 'Electrical', 'Fire', 'Chemical Exposure',
        'Manual Handling', 'Struck By Object', 'Confined Space', 'Excavation', 'Scaffolding',
        'Lifting Operations', 'Moving Machinery', 'Noise', 'Dust / Airborne Particles',
        'Environmental', 'Security', 'Ergonomic', 'Other',
    ];
}
