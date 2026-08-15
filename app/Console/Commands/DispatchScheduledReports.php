<?php

namespace App\Console\Commands;

use App\Mail\ScheduledReportMail;
use App\Models\CustomReport;
use App\Models\ScheduledReport;
use App\Notifications\BiNotification;
use App\Services\BiNotificationRecipients;
use App\Services\Construction\BusinessIntelligenceService;
use App\Services\Construction\ReportExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class DispatchScheduledReports extends Command
{
    protected $signature = 'bi:dispatch-scheduled-reports';

    protected $description = 'Generate and deliver every scheduled report whose next_run_at has passed';

    public function handle(ReportExportService $exportService, BusinessIntelligenceService $bi, BiNotificationRecipients $recipients): void
    {
        $due = ScheduledReport::query()
            ->where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->with('customReport')
            ->get();

        if ($due->isEmpty()) {
            $this->info('No scheduled reports are due.');

            return;
        }

        foreach ($due as $schedule) {
            $companyId = $schedule->company_id;
            $sections = [];
            $kpis = [];
            $title = $schedule->name;

            if ($schedule->source_type === 'custom_report' && $schedule->customReport) {
                [$title, $sections] = $this->buildCustomReportSections($schedule->customReport);
            } else {
                $title = $schedule->name . ' (' . $schedule->source_key . ')';
                $kpis = $this->buildKpiSnapshot($bi, $companyId);
                $sections = [['title' => 'Company KPI Snapshot', 'columns' => ['KPI', 'Value'], 'rows' => collect($kpis)->map(fn ($kpi) => [$kpi['label'], $kpi['value']])->all()]];
            }

            // Only PDF is generated for email delivery — CSV/Excel/Word/JSON
            // remain available on-demand via Data Exports and Custom Reports.
            $format = 'pdf';
            $content = $exportService->pdfContent($title, $sections, $kpis);
            $mime = 'application/pdf';
            $extension = 'pdf';

            $filename = str($title)->slug()->toString() . '-' . now()->format('Y-m-d') . '.' . $extension;

            \App\Models\ReportExport::create([
                'company_id' => $companyId,
                'report_name' => $title,
                'report_type' => 'scheduled',
                'format' => $format,
                'generated_by_id' => $schedule->created_by_id,
                'file_size' => strlen($content),
            ]);

            $recipientEmails = collect($schedule->recipients ?? []);

            if ($schedule->delivery_method === 'Email' && $recipientEmails->isNotEmpty()) {
                foreach ($recipientEmails as $email) {
                    Mail::to($email)->send(new ScheduledReportMail($title, $schedule->frequency, $content, $filename, $mime));
                }
            }

            $users = $recipients->forCompany($companyId);
            if ($users->isNotEmpty()) {
                Notification::send($users, new BiNotification('scheduled_report_ready', [
                    'reference' => $schedule->name,
                ]));
            }

            $schedule->markRun();

            $this->info("Dispatched scheduled report: {$schedule->name}");
        }

        $this->info("Scheduled report dispatch complete. {$due->count()} report(s) processed.");
    }

    protected function buildCustomReportSections(CustomReport $report): array
    {
        $bi = app(BusinessIntelligenceService::class);
        $definition = $bi->dataSourceDefinition($report->data_source);

        if (! $definition) {
            return [$report->name, []];
        }

        $model = $definition['model'];
        $columns = array_values(array_intersect($report->fields ?? $definition['columns'], $definition['columns']));

        if (empty($columns)) {
            $columns = $definition['columns'];
        }

        $rows = $model::query()
            ->when($report->company_id, fn ($q) => $q->where('company_id', $report->company_id))
            ->select($columns)
            ->limit(500)
            ->get()
            ->map(fn ($record) => collect($columns)->map(fn ($col) => (string) ($record->{$col} ?? '—'))->all())
            ->all();

        return [$report->name, [['title' => $report->data_source, 'columns' => $columns, 'rows' => $rows]]];
    }

    protected function buildKpiSnapshot(BusinessIntelligenceService $bi, ?int $companyId): array
    {
        $currency = session('currency', 'KES');

        return [
            ['label' => 'Total Projects', 'value' => (string) $bi->totalProjects($companyId)],
            ['label' => 'Active Projects', 'value' => (string) $bi->activeProjects($companyId)],
            ['label' => 'Projects at Risk', 'value' => (string) $bi->projectsAtRisk($companyId)],
            ['label' => 'Total Revenue', 'value' => $currency . ' ' . number_format($bi->totalRevenue($companyId), 0)],
            ['label' => 'Profit Margin', 'value' => $bi->profitMargin($companyId) . '%'],
            ['label' => 'Safety Score', 'value' => number_format($bi->safetyScore($companyId), 1)],
            ['label' => 'Quality Score', 'value' => $bi->qualityScore($companyId) . '%'],
        ];
    }
}
