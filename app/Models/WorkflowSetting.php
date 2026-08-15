<?php

namespace App\Models;

class WorkflowSetting extends BaseModel
{
    protected $guarded = [];

    public static function getSetting(int $companyId, string $key, $default = null): mixed
    {
        $setting = static::withoutGlobalScope('company')
            ->where('company_id', $companyId)
            ->where('key', $key)
            ->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => (bool) $setting->value,
            'integer' => (int) $setting->value,
            'float' => (float) $setting->value,
            default => $setting->value,
        };
    }

    public static function setSetting(int $companyId, string $key, $value, string $type = 'string'): void
    {
        static::withoutGlobalScope('company')->updateOrCreate(
            ['company_id' => $companyId, 'key' => $key],
            ['value' => $value, 'type' => $type]
        );
    }
}
