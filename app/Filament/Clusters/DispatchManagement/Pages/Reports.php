<?php

namespace App\Filament\Clusters\DispatchManagement\Pages;

use App\Filament\Clusters\DispatchManagement;
use App\Models\DispatchOrder;
use App\Models\Employee;
use App\Services\Construction\ReportExportService;
use App\Services\Quarry\DispatchAnalyticsService;
use App\Services\Quarry\DispatchSchedulingService;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class Reports extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'dispatch.export';
    }

    protected static ?string $cluster = DispatchManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.clusters.dispatch-management.pages.reports';

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.098 4.02 8.25 4.982 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />',
            'title' => 'Dispatch Reports',
            'subtitle' => 'Dispatch register, delivery, driver performance, truck utilization, customer delivery, late delivery, revenue, product, and executive reporting.',
        ]);
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Dispatch Register', 'description' => 'Every dispatch order created today.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportDispatchRegister'],
            ['label' => 'Delivery Report', 'description' => 'Deliveries over the last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportDeliveryReport'],
            ['label' => 'Driver Performance Report', 'description' => 'Trips and license validity per driver.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportDriverPerformanceReport'],
            ['label' => 'Truck Utilization Report', 'description' => 'Trips per truck over the last 7 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportTruckUtilizationReport'],
            ['label' => 'Customer Delivery Report', 'description' => 'Deliveries grouped by customer.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportCustomerDeliveryReport'],
            ['label' => 'Late Delivery Report', 'description' => 'Deliveries that arrived after ETA.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportLateDeliveryReport'],
            ['label' => 'Revenue Report', 'description' => 'Invoiced revenue over the last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportRevenueReport'],
            ['label' => 'Product Dispatch Report', 'description' => 'Tonnes dispatched by product.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportProductDispatchReport'],
            ['label' => 'Executive Dispatch Summary', 'description' => 'KPI snapshot across the whole dispatch operation.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportExecutiveSummary'],
        ];
    }

    public function exportDispatchRegister()
    {
        $rows = DispatchOrder::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereDate('created_at', today())
            ->with(['customer', 'material'])
            ->get()
            ->map(fn (DispatchOrder $o) => [$o->dispatch_number, $o->customer?->company_name, $o->material?->name, $o->vehicle_registration ?? '—', $o->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Dispatch Register — ' . today()->toFormattedDateString(), [
            ['title' => "Today's Dispatches", 'columns' => ['Dispatch #', 'Customer', 'Product', 'Vehicle', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportDeliveryReport()
    {
        $rows = DispatchOrder::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('created_at', '>=', now()->subDays(30))
            ->whereIn('status', ['Exited', 'En Route', 'Delivered', 'Delayed'])
            ->with('customer')
            ->get()
            ->map(fn (DispatchOrder $o) => [
                $o->dispatch_number, $o->customer?->company_name,
                $o->exit_at?->format('d M Y H:i') ?? '—', $o->arrived_at?->format('d M Y H:i') ?? '—', $o->status,
            ])
            ->all();

        return app(ReportExportService::class)->toPdf('Delivery Report (30 Days)', [
            ['title' => 'Deliveries', 'columns' => ['Dispatch #', 'Customer', 'Departed', 'Arrived', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportDriverPerformanceReport()
    {
        $service = app(DispatchSchedulingService::class);

        $rows = Employee::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('status', 'Active')
            ->get()
            ->map(fn (Employee $e) => [
                $e->full_name,
                $e->license_expiry?->format('d M Y') ?? '—',
                $service->driverTripsToday($e),
                $service->driverPassesFitness($e, $this->companyId()) ? 'Fit' : 'Not Fit',
            ])
            ->all();

        return app(ReportExportService::class)->toPdf('Driver Performance Report', [
            ['title' => 'Drivers', 'columns' => ['Driver', 'License Expiry', 'Trips Today', 'Fitness'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportTruckUtilizationReport()
    {
        $svc = app(DispatchAnalyticsService::class);
        $rows = collect($svc->truckUtilization($this->companyId()))->map(fn ($trips, $plate) => [$plate, $trips])->values()->all();

        return app(ReportExportService::class)->toPdf('Truck Utilization Report (7 Days)', [
            ['title' => 'Trips per Truck', 'columns' => ['Truck', 'Trips'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportCustomerDeliveryReport()
    {
        $svc = app(DispatchAnalyticsService::class);
        $rows = collect($svc->topCustomers($this->companyId(), 30, 100))->map(fn ($tonnes, $customer) => [$customer, number_format($tonnes, 1)])->values()->all();

        return app(ReportExportService::class)->toPdf('Customer Delivery Report (30 Days)', [
            ['title' => 'Deliveries by Customer', 'columns' => ['Customer', 'Tonnes'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportLateDeliveryReport()
    {
        $rows = DispatchOrder::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereNotNull('arrived_at')->whereNotNull('eta')
            ->whereColumn('arrived_at', '>', 'eta')
            ->where('arrived_at', '>=', now()->subDays(30))
            ->with('customer')
            ->get()
            ->map(fn (DispatchOrder $o) => [
                $o->dispatch_number, $o->customer?->company_name,
                $o->eta?->format('d M Y H:i'), $o->arrived_at?->format('d M Y H:i'),
                $o->eta->diffInMinutes($o->arrived_at) . ' min late',
            ])
            ->all();

        return app(ReportExportService::class)->toPdf('Late Delivery Report (30 Days)', [
            ['title' => 'Late Deliveries', 'columns' => ['Dispatch #', 'Customer', 'ETA', 'Arrived', 'Delay'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportRevenueReport()
    {
        $svc = app(DispatchAnalyticsService::class);
        $rows = collect($svc->revenueTrend($this->companyId()))->map(fn ($amount, $day) => [$day, session('currency', 'KES') . ' ' . number_format($amount, 2)])->values()->all();

        return app(ReportExportService::class)->toPdf('Revenue Report (14 Days)', [
            ['title' => 'Daily Revenue', 'columns' => ['Date', 'Revenue'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportProductDispatchReport()
    {
        $svc = app(DispatchAnalyticsService::class);
        $rows = collect($svc->topProducts($this->companyId(), 30, 100))->map(fn ($tonnes, $product) => [$product, number_format($tonnes, 1)])->values()->all();

        return app(ReportExportService::class)->toPdf('Product Dispatch Report (30 Days)', [
            ['title' => 'Tonnes by Product', 'columns' => ['Product', 'Tonnes'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportExecutiveSummary()
    {
        $svc = app(DispatchAnalyticsService::class);
        $companyId = $this->companyId();

        $kpis = [
            ['label' => "Today's Dispatches", 'value' => (string) $svc->todaysDispatchesCount($companyId)],
            ['label' => "Today's Tonnes Delivered", 'value' => number_format($svc->todaysTonnesDelivered($companyId), 1) . ' t'],
            ['label' => "Today's Revenue", 'value' => session('currency', 'KES') . ' ' . number_format($svc->todaysRevenue($companyId), 2)],
            ['label' => 'Avg. Turnaround', 'value' => number_format($svc->averageTurnaroundMinutes($companyId), 0) . ' min'],
            ['label' => 'Avg. Delivery Time', 'value' => number_format($svc->averageDeliveryTimeMinutes($companyId), 0) . ' min'],
            ['label' => 'On-Time Delivery', 'value' => $svc->onTimeDeliveryPercent($companyId) . '%'],
            ['label' => 'Delayed Dispatches', 'value' => (string) $svc->delayedDispatchesCount($companyId)],
            ['label' => 'Pending Approvals', 'value' => (string) $svc->pendingDispatchesCount($companyId)],
        ];

        $recentRows = DispatchOrder::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (DispatchOrder $o) => [$o->dispatch_number, $o->customer?->company_name, $o->net_weight, $o->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Executive Dispatch Summary', [
            ['title' => 'Recent Dispatches', 'columns' => ['Dispatch #', 'Customer', 'Net Weight (t)', 'Status'], 'rows' => $recentRows],
        ], $kpis, $companyId);
    }
}
