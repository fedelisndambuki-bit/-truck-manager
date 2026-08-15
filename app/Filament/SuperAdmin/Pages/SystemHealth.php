<?php

namespace App\Filament\SuperAdmin\Pages;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Filament\Pages\Page;

class SystemHealth extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'System Health';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 80;

    protected static string $view = 'filament.super-admin.pages.system-health';

    public function getViewData(): array
    {
        $diskTotal = @disk_total_space(base_path()) ?: 0;
        $diskFree = @disk_free_space(base_path()) ?: 0;
        $diskUsedPercent = $diskTotal > 0 ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 2) : null;

        $cpu = $this->cpuUsagePercent();
        $ram = $this->ramUsagePercent();

        $dbStatus = 'down';
        try {
            DB::connection()->getPdo();
            $dbStatus = 'up';
        } catch (\Throwable $e) {
            $dbStatus = 'down';
        }

        $queuePending = Schema::hasTable('jobs') ? DB::table('jobs')->count() : null;
        $queueFailed = Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null;

        $ollamaUrl = rtrim((string) env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'), '/');
        $ollamaStatus = 'down';
        try {
            $response = Http::timeout(2)->get($ollamaUrl . '/api/tags');
            $ollamaStatus = $response->successful() ? 'up' : 'down';
        } catch (\Throwable $e) {
            $ollamaStatus = 'down';
        }

        return [
            'cpu' => $cpu,
            'ram' => $ram,
            'diskUsedPercent' => $diskUsedPercent,
            'dbStatus' => $dbStatus,
            'queuePending' => $queuePending,
            'queueFailed' => $queueFailed,
            'ollamaStatus' => $ollamaStatus,
            'phpVersion' => PHP_VERSION,
            'appEnv' => config('app.env'),
        ];
    }

    private function cpuUsagePercent(): ?float
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = @shell_exec('wmic cpu get loadpercentage /value');
            if ($output && preg_match('/LoadPercentage=(\d+)/i', $output, $matches)) {
                return (float) $matches[1];
            }
            return null;
        }

        $load = sys_getloadavg();
        if (! is_array($load) || ! isset($load[0])) {
            return null;
        }

        return round($load[0] * 100, 2);
    }

    private function ramUsagePercent(): ?float
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = @shell_exec('wmic OS get FreePhysicalMemory,TotalVisibleMemorySize /Value');
            if (! $output) {
                return null;
            }

            preg_match('/FreePhysicalMemory=(\d+)/i', $output, $freeMatch);
            preg_match('/TotalVisibleMemorySize=(\d+)/i', $output, $totalMatch);

            if (! isset($freeMatch[1], $totalMatch[1])) {
                return null;
            }

            $free = (float) $freeMatch[1];
            $total = (float) $totalMatch[1];
            if ($total <= 0) {
                return null;
            }

            return round((($total - $free) / $total) * 100, 2);
        }

        return null;
    }
}
