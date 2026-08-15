<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Driver-portable date-bucketing SQL for trend charts. strftime() is
 * SQLite-only — the raw grouping expressions this replaces would throw a
 * syntax error on MySQL/PostgreSQL in production.
 */
class SqlDateFormat
{
    public static function daily(string $column, string $alias): string
    {
        return match (DB::getDriverName()) {
            'mysql' => "DATE_FORMAT({$column}, '%Y-%m-%d') as {$alias}",
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM-DD') as {$alias}",
            default => "strftime('%Y-%m-%d', {$column}) as {$alias}",
        };
    }

    public static function monthly(string $column, string $alias): string
    {
        return match (DB::getDriverName()) {
            'mysql' => "DATE_FORMAT({$column}, '%Y-%m') as {$alias}",
            'pgsql' => "TO_CHAR({$column}, 'YYYY-MM') as {$alias}",
            default => "strftime('%Y-%m', {$column}) as {$alias}",
        };
    }

    public static function hourly(string $column, string $alias): string
    {
        return match (DB::getDriverName()) {
            'mysql' => "DATE_FORMAT({$column}, '%H:00') as {$alias}",
            'pgsql' => "TO_CHAR({$column}, 'HH24:00') as {$alias}",
            default => "strftime('%H:00', {$column}) as {$alias}",
        };
    }
}
