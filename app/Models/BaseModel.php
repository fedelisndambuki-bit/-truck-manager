<?php

namespace App\Models;

use App\Traits\MultiTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

abstract class BaseModel extends Model
{
    use MultiTenantScope, HasFactory;

    protected static function booted()
    {
        static::created(function ($model) {
            static::logActivity($model, 'Created');
        });

        static::updated(function ($model) {
            static::logActivity($model, 'Updated');
        });

        static::deleted(function ($model) {
            static::logActivity($model, 'Deleted');
        });
    }

    protected static function logActivity($model, $action)
    {
        // Don't log system logs to avoid recursion
        if ($model instanceof \App\Models\SystemLog) {
            return;
        }

        $user = auth()->user();
        $modelName = (new \ReflectionClass($model))->getShortName();
        $description = "{$action} {$modelName} (ID: {$model->id})";

        \App\Models\SystemLog::create([
            'company_id' => $model->company_id ?? $user?->company_id,
            'user_id' => $user?->id,
            'action' => "{$action} {$modelName}",
            'description' => $description,
            'severity' => 'info',
            'metadata' => json_encode($model->getAttributes()),
        ]);
    }

    /**
     * Get the company that owns the record.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
