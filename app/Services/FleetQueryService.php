<?php

namespace App\Services;

use App\Models\Truck;
use App\Models\Trip;
use App\Models\FuelLog;
use App\Models\Maintenance;
use App\Models\Employee;
use App\Models\Invoice;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Services\PredictiveMaintenanceService;
use App\Services\FuelFraudService;
use App\Services\DriverRiskService;
use App\Services\AIService;

class FleetQueryService
{
    public function handle(int $companyId, string $message): ?string
    {
        $message = trim($message);
        if (!Str::startsWith($message, '/')) {
            return null;
        }

        $parts = preg_split('/\s+/', $message);
        $command = strtolower(ltrim(array_shift($parts), '/'));
        $arg = strtolower(implode(' ', $parts));

        $user = auth()->user();
        $isSuperAdmin = $user?->hasRole('Super Admin') ?? false;

        if ($command === 'help') {
            return $this->helpText($isSuperAdmin);
        }

        if ($isSuperAdmin) {
            return match ($command) {
                'companies' => $this->companiesSummary($arg),
                'plans' => $this->plansSummary($arg),
                'subscriptions' => $this->subscriptionsSummary($arg),
                'settings' => $this->settingsSummary($arg),
                'logs' => $this->logsSummary($arg),
                default => 'Unknown Super Admin command. Type /help for available commands.',
            };
        }

        if ($command === 'trips') {
            if (trim($arg) === 'delayed-today') {
                return $this->tripsDelayedToday($companyId);
            }
            return $this->tripsSummary($companyId, $arg);
        }

        if ($command === 'truck') {
            if (Str::startsWith($arg, 'next-maintenance')) {
                $plate = trim(Str::after($arg, 'next-maintenance'));
                return $this->truckNextMaintenance($companyId, $plate);
            }
            return $this->truckInfo($companyId, $arg);
        }

        if ($command === 'fuel') {
            return $this->fuelSummary($companyId, $arg);
        }

        if ($command === 'maintenance') {
            return $this->maintenanceDue($companyId, $arg);
        }

        if ($command === 'driver') {
            if (trim($arg) === 'license-expiring-this-month') {
                return $this->driversLicenseExpiringThisMonth($companyId);
            }
            return $this->driverInfo($companyId, $arg);
        }

        if ($command === 'invoices') {
            if (trim($arg) === 'unpaid-this-month') {
                return $this->invoicesUnpaidThisMonth($companyId);
            }
            return $this->invoicesSummary($companyId, $arg);
        }

        if ($command === 'predict') {
            if (str_starts_with($arg, 'maintenance')) {
                $plate = trim(str_replace('maintenance', '', $arg));
                return $this->predictiveMaintenance($companyId, $plate);
            }
            return 'Usage: /predict maintenance [plate|top]';
        }

        if ($command === 'fraud') {
            if (trim($arg) === 'fuel recent' || trim($arg) === 'fuel') {
                return $this->fuelFraudRecent($companyId);
            }
            return 'Usage: /fraud fuel recent';
        }

        if ($command === 'insights') {
            if (trim($arg) === 'daily') {
                return $this->dailyInsights($companyId);
            }
            return 'Usage: /insights daily';
        }

        return 'Unknown command. Type /help for available commands.';
    }

    private function helpText(bool $isSuperAdmin = false): string
    {
        if ($isSuperAdmin) {
            return implode("\n", [
                'Super Admin Commands:',
                '/companies [list|count]',
                '/plans [list]',
                '/subscriptions [active|expired]',
                '/settings [list]',
                '/logs [recent]',
                '/insights daily',
            ]);
        }

        return implode("\n", [
            'Commands:',
            '/trips [active|scheduled|completed|in-progress]',
            '/trips delayed-today',
            '/truck [plate]',
            '/truck next-maintenance [plate]',
            '/fuel [this-month|last-month]',
            '/maintenance due',
            '/driver [name]',
            '/driver license-expiring-this-month',
            '/invoices sent-this-week',
            '/invoices unpaid-this-month',
            '/predict maintenance [plate|top]',
            '/fraud fuel recent',
            '/insights daily',
        ]);
    }

    private function companiesSummary(string $arg): string
    {
        $count = \App\Models\Company::count();
        if ($arg === 'count') return "Total companies registered: {$count}";
        
        $recent = \App\Models\Company::latest()->take(5)->pluck('name')->toArray();
        $lines = ["Companies: {$count}", "Recent:"];
        foreach ($recent as $name) $lines[] = "• {$name}";
        return implode("\n", $lines);
    }

    private function plansSummary(string $arg): string
    {
        $plans = \App\Models\Plan::all();
        $lines = ["Available Plans: " . $plans->count()];
        foreach ($plans as $p) $lines[] = "• {$p->name} (KES " . number_format($p->price) . ")";
        return implode("\n", $lines);
    }

    private function subscriptionsSummary(string $arg): string
    {
        $q = \App\Models\Subscription::query();
        if ($arg === 'active') $q->where('status', 'active');
        if ($arg === 'expired') $q->where('status', 'expired');
        
        $count = $q->count();
        $recent = $q->with('company')->latest()->take(5)->get();
        
        $lines = ["Subscriptions (" . ($arg ?: 'all') . "): {$count}"];
        foreach ($recent as $s) {
            $lines[] = "• " . ($s->company->name ?? 'Unknown') . " - " . $s->plan_name . " (" . $s->status . ")";
        }
        return implode("\n", $lines);
    }

    private function settingsSummary(string $arg): string
    {
        $count = \App\Models\PlatformSetting::count();
        $recent = \App\Models\PlatformSetting::latest()->take(5)->get();
        $lines = ["Platform Settings: {$count}", "Recent Changes:"];
        foreach ($recent as $s) $lines[] = "• {$s->key}: " . Str::limit($s->value, 20);
        return implode("\n", $lines);
    }

    private function logsSummary(string $arg): string
    {
        $count = \App\Models\AiLog::count();
        $errors = \App\Models\AiLog::where('status', 'error')->count();
        return "System AI Logs: {$count} total, {$errors} errors. Platform health is stable.";
    }

    private function tripsSummary(int $companyId, string $statusArg): string
    {
        $map = [
            'active' => ['In Progress', 'Scheduled'],
            'scheduled' => ['Scheduled'],
            'completed' => ['Completed'],
            'in-progress' => ['In Progress'],
        ];

        $statuses = $map[$statusArg] ?? null;
        $q = Trip::where('company_id', $companyId);
        if ($statuses) {
            $q->whereIn('status', $statuses);
        }
        $total = $q->count();
        $recent = $q->latest()->take(5)->get()->map(function ($t) {
            $label = $t->route_name ?: trim(($t->origin ?: '').' → '.($t->destination ?: ''));
            if ($label === '→' || $label === '→ ') {
                $label = 'Trip #'.$t->id;
            }
            return ($t->status ?: 'N/A').' • '.$label;
        })->toArray();

        $title = $statuses ? implode(', ', $statuses) : 'All';
        $lines = ['Trips '.$title.': '.$total];
        foreach ($recent as $r) {
            $lines[] = '• '.$r;
        }
        return implode("\n", $lines);
    }

    private function predictiveMaintenance(int $companyId, string $plate): string
    {
        $svc = app(PredictiveMaintenanceService::class);
        $plate = trim($plate);
        if ($plate === '' || $plate === 'top') {
            $risks = $svc->topRisks($companyId, 5);
            if (empty($risks)) {
                return 'No trucks with notable maintenance risk.';
            }
            $lines = ['Top maintenance risks:'];
            foreach ($risks as $r) {
                $win = $r['service_window'];
                $lines[] = '• '.$r['plate_number'].' • '.$r['probability'].'% • RUL '.($r['rul_days'] ?? 'N/A').'d • '.$win[0].' - '.$win[1];
            }
            return implode("\n", $lines);
        }
        $truck = \App\Models\Truck::where('company_id', $companyId)->where('plate_number', $plate)->first();
        if (! $truck) {
            return 'Truck not found.';
        }
        $r = $svc->assessTruck($companyId, $truck);
        if (! $r) {
            return 'No data to assess this truck.';
        }
        $win = $r['service_window'];
        return 'Predictive maintenance • '.$r['plate_number'].' • '.$r['probability'].'% • RUL '.($r['rul_days'] ?? 'N/A').'d • '.$win[0].' - '.$win[1];
    }

    private function fuelFraudRecent(int $companyId): string
    {
        $svc = app(FuelFraudService::class);
        $flags = $svc->recentFlags($companyId, 10);
        if (empty($flags)) {
            return 'No recent fuel anomalies.';
        }
        $lines = ['Recent fuel anomalies:'];
        foreach ($flags as $f) {
            $plate = optional(\App\Models\Truck::find($f['truck_id']))?->plate_number ?? 'Truck #'.$f['truck_id'];
            $lines[] = '• '.$f['date'].' • '.$plate.' • '.$f['type'].' • '.$f['details'];
        }
        return implode("\n", $lines);
    }

    private function dailyInsights(int $companyId): string
    {
        $user = auth()->user();
        $ai = app(AIService::class);
        $res = $ai->generateDailyInsights($companyId, $user->id);
        return $res['success'] ? (string) $res['response'] : 'AI unavailable.';
    }

    private function tripsDelayedToday(int $companyId): string
    {
        $start = now()->startOfDay();
        $end = now()->endOfDay();
        $q = Trip::where('company_id', $companyId)
            ->whereBetween('expected_arrival', [$start, $end])
            ->where(function ($qq) {
                $qq->whereNull('actual_arrival')
                   ->orWhereColumn('actual_arrival', '>', 'expected_arrival');
            });
        $count = $q->count();
        $items = $q->latest('expected_arrival')->take(5)->get()->map(function ($t) {
            $label = $t->route_name ?? trim(($t->origin ?: '').' → '.($t->destination ?: ''));
            if ($label === '' || $label === '→' || $label === '→ ') {
                $label = 'Trip #'.$t->id;
            }
            $eta = $t->expected_arrival ? $t->expected_arrival->format('H:i') : 'N/A';
            return $label.' • ETA '.$eta;
        })->toArray();
        $lines = ['Trips delayed today: '.$count];
        foreach ($items as $i) {
            $lines[] = '• '.$i;
        }
        return implode("\n", $lines);
    }

    private function truckInfo(int $companyId, string $plate): string
    {
        $plate = trim($plate);
        if ($plate === '') {
            return 'Usage: /truck [plate]';
        }
        $truck = Truck::where('company_id', $companyId)->where('plate_number', $plate)->first();
        if (!$truck) {
            return 'Truck not found.';
        }
        $driver = $truck->driver?->full_name ?? 'No Driver';
        $mileage = $truck->current_mileage ? number_format($truck->current_mileage).' KM' : 'N/A';
        return implode("\n", [
            'Plate: '.$truck->plate_number,
            'Status: '.($truck->status ?? 'N/A'),
            'Driver: '.$driver,
            'Mileage: '.$mileage,
            'Type: '.($truck->truck_type ?? 'N/A'),
        ]);
    }

    private function fuelSummary(int $companyId, string $range): string
    {
        $now = now();
        if ($range === 'last-month') {
            $month = $now->copy()->subMonth()->month;
            $year = $now->copy()->subMonth()->year;
        } else {
            $month = $now->month;
            $year = $now->year;
        }
        $q = FuelLog::where('company_id', $companyId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year);
        $litres = (float) $q->sum('quantity_litres');
        $cost = (float) $q->sum('total_cost');
        return 'Fuel '.$range.': '.number_format($litres, 2).' L • KES '.number_format($cost, 2);
    }

    private function maintenanceDue(int $companyId, string $arg): string
    {
        $q = Maintenance::where('company_id', $companyId)
            ->where('status', 'Pending')
            ->where('maintenance_date', '<=', now()->addDays(30));
        $count = $q->count();
        $items = $q->latest()->take(5)->get()->map(function ($m) {
            $plate = $m->truck->plate_number ?? 'N/A';
            $date = $m->maintenance_date ? $m->maintenance_date->format('M d, Y') : 'N/A';
            return $plate.' • '.$m->maintenance_type.' • '.$date;
        })->toArray();
        $lines = ['Maintenance due (30d): '.$count];
        foreach ($items as $i) {
            $lines[] = '• '.$i;
        }
        return implode("\n", $lines);
    }

    private function driverInfo(int $companyId, string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return 'Usage: /driver [name]';
        }
        $candidates = Employee::where('company_id', $companyId)
            ->latest()
            ->take(200)
            ->get();
        $matches = $candidates->filter(function ($e) use ($name) {
            $n = (string) ($e->full_name ?? $e->name ?? '');
            return $n !== '' && stripos($n, $name) !== false;
        })->take(5);
        if ($matches->isEmpty()) {
            return 'No drivers match that name.';
        }
        $lines = ["Drivers matching \"{$name}\":"];
        foreach ($matches as $e) {
            $n = (string) ($e->full_name ?? $e->name ?? ('Driver #'.$e->id));
            $status = $e->status ?? 'N/A';
            $lic = $e->license_expiry ? \Illuminate\Support\Carbon::parse($e->license_expiry)->format('M d, Y') : 'N/A';
            $truck = $e->truck?->plate_number ?? 'Unassigned';
            $lines[] = '• '.$n.' • '.$status.' • License: '.$lic.' • '.$truck;
        }
        return implode("\n", $lines);
    }

    private function invoicesSummary(int $companyId, string $arg): string
    {
        $arg = trim($arg);
        if ($arg !== 'sent-this-week') {
            return 'Usage: /invoices sent-this-week';
        }
        $start = now()->startOfWeek();
        $end = now()->endOfWeek();
        $q = Invoice::where('company_id', $companyId)
            ->where('status', 'sent')
            ->whereBetween('updated_at', [$start, $end]);
        $count = $q->count();
        $items = $q->latest()->take(5)->get()->map(function ($inv) {
            $num = $inv->invoice_number ?? '#'.$inv->id;
            $date = $inv->updated_at ? $inv->updated_at->format('M d') : 'N/A';
            return $num.' • '.$date;
        })->toArray();
        $lines = ['Invoices sent this week: '.$count];
        foreach ($items as $i) {
            $lines[] = '• '.$i;
        }
        return implode("\n", $lines);
    }

    private function invoicesUnpaidThisMonth(int $companyId): string
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $q = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['draft', 'sent', 'overdue'])
            ->whereBetween('due_date', [$start, $end]);
        $count = $q->count();
        $sum = (float) $q->sum('total_amount');
        $items = $q->latest('due_date')->take(5)->get()->map(function ($inv) {
            $num = $inv->invoice_number ?? '#'.$inv->id;
            $due = $inv->due_date ? $inv->due_date->format('M d') : 'N/A';
            $status = $inv->status ?? 'N/A';
            return $num.' • Due '.$due.' • '.$status;
        })->toArray();
        $lines = ['Unpaid invoices this month: '.$count.' • Total KES '.number_format($sum, 2)];
        foreach ($items as $i) {
            $lines[] = '• '.$i;
        }
        return implode("\n", $lines);
    }

    private function truckNextMaintenance(int $companyId, string $plate): string
    {
        $plate = trim($plate);
        if ($plate === '') {
            return 'Usage: /truck next-maintenance [plate]';
        }
        $truck = Truck::where('company_id', $companyId)->where('plate_number', $plate)->first();
        if (!$truck) {
            return 'Truck not found.';
        }
        $now = now();
        $next = Maintenance::where('company_id', $companyId)
            ->where('truck_id', $truck->id)
            ->whereNotNull('next_maintenance_date')
            ->where('next_maintenance_date', '>=', $now->toDateString())
            ->orderBy('next_maintenance_date')
            ->first();
        if ($next) {
            $date = Carbon::parse($next->next_maintenance_date)->format('M d, Y');
            $type = $next->maintenance_type ?? 'Service';
            return 'Next maintenance for '.$plate.': '.$type.' on '.$date;
        }
        $last = Maintenance::where('company_id', $companyId)
            ->where('truck_id', $truck->id)
            ->orderByDesc('maintenance_date')
            ->first();
        if ($last) {
            $when = $last->maintenance_date ? $last->maintenance_date->format('M d, Y') : 'N/A';
            $nxt = $last->next_maintenance_date ? Carbon::parse($last->next_maintenance_date)->format('M d, Y') : 'N/A';
            return 'No upcoming record. Last: '.$when.'. Next due: '.$nxt.'.';
        }
        return 'No maintenance records for '.$plate.'.';
    }

    private function driversLicenseExpiringThisMonth(int $companyId): string
    {
        $start = now()->startOfMonth();
        $end = now()->endOfMonth();
        $q = Employee::where('company_id', $companyId)
            ->whereNotNull('license_expiry')
            ->whereBetween('license_expiry', [$start->toDateString(), $end->toDateString()]);
        $count = $q->count();
        $items = $q->orderBy('license_expiry')->take(5)->get()->map(function ($e) {
            $name = $e->full_name ?? $e->name ?? ('Driver #'.$e->id);
            $date = $e->license_expiry ? $e->license_expiry->format('M d, Y') : 'N/A';
            $truck = $e->truck?->plate_number ?? 'Unassigned';
            return $name.' • '.$date.' • '.$truck;
        })->toArray();
        $lines = ['Licenses expiring this month: '.$count];
        foreach ($items as $i) {
            $lines[] = '• '.$i;
        }
        return implode("\n", $lines);
    }
}
