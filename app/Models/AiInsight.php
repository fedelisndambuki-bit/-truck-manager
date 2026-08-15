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
            'declining_productivity', 'declining_on_time_performance' => 'heroicon-o-arrow-trending-down',
            'equipment_risk', 'crusher_risk' => 'heroicon-o-wrench-screwdriver',
            'schedule_slippage' => 'heroicon-o-clock',
            'safety_risk', 'blast_safety_risk' => 'heroicon-o-shield-exclamation',
            'material_waste_anomaly' => 'heroicon-o-cube',
            'high_performing_team', 'high_performing_driver' => 'heroicon-o-trophy',
            'cost_saving_recommendation' => 'heroicon-o-light-bulb',
            'predictive_maintenance_risk' => 'heroicon-o-truck',
            'driver_risk' => 'heroicon-o-user',
            'license_expired', 'license_expiring' => 'heroicon-o-identification',
            'overdue_maintenance' => 'heroicon-o-wrench',
            'underutilized_truck' => 'heroicon-o-pause-circle',
            'production_shortfall' => 'heroicon-o-arrow-trending-down',
            'dispatch_delay' => 'heroicon-o-truck',
            'weighbridge_bottleneck' => 'heroicon-o-scale',
            'stockpile_low' => 'heroicon-o-cube',
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

    /**
     * The detector service for a given industry — each shares this same
     * table/model and the narrate()-with-Ollama-fallback pattern, but reads
     * from that industry's own real modules.
     */
    public static function serviceForIndustry(?string $industry): string
    {
        return match ($industry) {
            'construction' => \App\Services\Construction\AiInsightsService::class,
            'quarry' => \App\Services\Quarry\AiInsightsService::class,
            default => \App\Services\Fleet\AiInsightsService::class,
        };
    }
}
