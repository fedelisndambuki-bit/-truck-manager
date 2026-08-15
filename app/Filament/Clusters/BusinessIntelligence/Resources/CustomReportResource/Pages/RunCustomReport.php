<?php

namespace App\Filament\Clusters\BusinessIntelligence\Resources\CustomReportResource\Pages;

use App\Filament\Clusters\BusinessIntelligence\Resources\CustomReportResource;
use App\Models\CustomReport;
use App\Services\Construction\BusinessIntelligenceService;
use App\Services\Construction\ReportExportService;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;

class RunCustomReport extends Page
{
    protected static string $resource = CustomReportResource::class;

    protected static string $view = 'filament.clusters.business-intelligence.resources.custom-report-resource.pages.run-custom-report';

    public CustomReport $record;

    public function mount(CustomReport $record): void
    {
        $this->record = $record;
    }

    /**
     * Executes the stored report definition against the whitelisted model +
     * column set only — never raw user SQL. Any field named in `fields`,
     * `filters`, `group_by`, or `sort_by` that isn't in the data source's
     * column allowlist is silently dropped.
     */
    public function getResults(): array
    {
        $service = app(BusinessIntelligenceService::class);
        $definition = $service->dataSourceDefinition($this->record->data_source);

        if (! $definition) {
            return ['columns' => [], 'rows' => []];
        }

        $allowedColumns = $definition['columns'];
        $fields = array_values(array_intersect($this->record->fields ?? [], $allowedColumns));

        if (empty($fields)) {
            $fields = $allowedColumns;
        }

        /** @var \Illuminate\Database\Eloquent\Builder $query */
        $query = $definition['model']::query();

        foreach ($this->record->filters ?? [] as $filter) {
            if (empty($filter['field']) || ! in_array($filter['field'], $allowedColumns, true)) {
                continue;
            }

            $operator = in_array($filter['operator'], ['=', '!=', '>', '<', 'like'], true) ? $filter['operator'] : '=';

            if ($operator === 'like') {
                $query->where($filter['field'], 'like', '%' . $filter['value'] . '%');
            } else {
                $query->where($filter['field'], $operator, $filter['value']);
            }
        }

        $groupBy = in_array($this->record->group_by, $allowedColumns, true) ? $this->record->group_by : null;
        $sortBy = in_array($this->record->sort_by, $allowedColumns, true) ? $this->record->sort_by : null;

        if ($groupBy && $this->record->aggregate_function) {
            $aggregateField = collect($fields)->first(fn ($f) => $f !== $groupBy) ?? $groupBy;
            $function = in_array($this->record->aggregate_function, ['sum', 'count', 'avg', 'min', 'max'], true)
                ? $this->record->aggregate_function
                : 'count';

            $rows = $query->select($groupBy)
                ->selectRaw("{$function}({$aggregateField}) as aggregate_value")
                ->groupBy($groupBy)
                ->orderBy($groupBy)
                ->limit(500)
                ->get();

            return [
                'columns' => [$groupBy, strtoupper($function) . '(' . $aggregateField . ')'],
                'rows' => $rows->map(fn ($row) => [$row->{$groupBy}, round((float) $row->aggregate_value, 2)])->all(),
                'chart_labels' => $rows->pluck($groupBy)->all(),
                'chart_values' => $rows->pluck('aggregate_value')->map(fn ($v) => round((float) $v, 2))->all(),
            ];
        }

        $query->select($fields);

        if ($sortBy) {
            $query->orderBy($sortBy, $this->record->sort_direction === 'desc' ? 'desc' : 'asc');
        }

        $rows = $query->limit(500)->get();

        return [
            'columns' => $fields,
            'rows' => $rows->map(fn ($row) => collect($fields)->map(fn ($f) => $row->{$f})->all())->all(),
            'chart_labels' => [],
            'chart_values' => [],
        ];
    }

    public function exportPdf()
    {
        $results = $this->getResults();

        return app(ReportExportService::class)->toPdf($this->record->name, [
            ['title' => $this->record->name, 'columns' => $results['columns'], 'rows' => $results['rows']],
        ]);
    }

    public function exportCsv()
    {
        $results = $this->getResults();

        return app(ReportExportService::class)->toCsv($this->record->name, [
            ['title' => $this->record->name, 'columns' => $results['columns'], 'rows' => $results['rows']],
        ]);
    }

    public function exportExcel()
    {
        $results = $this->getResults();

        return app(ReportExportService::class)->toExcel($this->record->name, [
            ['title' => $this->record->name, 'columns' => $results['columns'], 'rows' => $results['rows']],
        ]);
    }
}
