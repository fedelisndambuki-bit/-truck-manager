<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiInsight extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'facts' => 'array',
        'generated_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function getIcon(string $type): string
    {
        return match ($type) {
            'budget_overrun_risk' => 'heroicon-o-exclamation-triangle',
            'declining_productivity' => 'heroicon-o-arrow-trending-down',
            'equipment_risk' => 'heroicon-o-wrench-screwdriver',
            'schedule_slippage' => 'heroicon-o-clock',
            'safety_risk' => 'heroicon-o-shield-exclamation',
            'material_waste_anomaly' => 'heroicon-o-cube',
            'high_performing_team' => 'heroicon-o-trophy',
            'cost_saving_recommendation' => 'heroicon-o-light-bulb',
            default => 'heroicon-o-sparkles',
        };
    }

    public static function getColor(string $severity): string
    {
        return match ($severity) {
            'Critical', 'High' => 'danger',
            'Medium' => 'warning',
            default => 'info',
        };
    }
}
