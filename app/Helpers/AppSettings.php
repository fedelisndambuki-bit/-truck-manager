<?php

namespace App\Helpers;

use App\Models\PlatformFinancialSetting;

class AppSettings
{
    /**
     * Returns the invoice tax rate as a decimal (e.g. 0.16 for 16%).
     * Reads from platform_financial_settings; falls back to 16% if not configured.
     */
    public static function taxRate(): float
    {
        return (float) (PlatformFinancialSetting::where('key', 'tax_rate')->value('value') ?? 0.16);
    }
}
