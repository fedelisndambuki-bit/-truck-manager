<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('platform_financial_settings')->upsert(
            [
                [
                    'key'         => 'tax_rate',
                    'value'       => 0.16,
                    'type'        => 'percentage',
                    'description' => 'Default invoice tax rate (e.g. 0.16 = 16% VAT)',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ],
            ],
            ['key'],
            ['value', 'description', 'updated_at']
        );
    }

    public function down(): void
    {
        DB::table('platform_financial_settings')->where('key', 'tax_rate')->delete();
    }
};
