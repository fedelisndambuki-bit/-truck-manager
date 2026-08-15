<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Services\Construction\BusinessIntelligenceService;
use App\Services\Construction\ReportExportService;
use App\Traits\HasModuleAccess;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

class DataExports extends Page
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'bi.export';
    }

    protected static ?string $cluster = BusinessIntelligence::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Data Exports';

    protected static ?int $navigationSort = 16;

    protected static ?string $title = 'Data Exports';

    protected static string $view = 'filament.clusters.business-intelligence.pages.data-exports';

    public string $dataSource = 'Projects';

    public function getDataSources(): array
    {
        return app(BusinessIntelligenceService::class)->dataSourceOptions();
    }

    protected function buildSections(): array
    {
        $definition = app(BusinessIntelligenceService::class)->dataSourceDefinition($this->dataSource);

        if (! $definition) {
            return ['columns' => [], 'rows' => []];
        }

        $model = $definition['model'];
        $columns = $definition['columns'];

        $rows = $model::query()->select($columns)->limit(500)->get()
            ->map(fn ($record) => collect($columns)->map(fn ($col) => (string) ($record->{$col} ?? '—'))->all())
            ->all();

        return ['columns' => $columns, 'rows' => $rows];
    }

    protected function exportTitle(): string
    {
        return $this->dataSource . ' Export';
    }

    public function exportPdf()
    {
        $data = $this->buildSections();

        return app(ReportExportService::class)->toPdf($this->exportTitle(), [
            ['title' => $this->dataSource, 'columns' => $data['columns'], 'rows' => $data['rows']],
        ]);
    }

    public function exportCsv()
    {
        $data = $this->buildSections();

        return app(ReportExportService::class)->toCsv($this->exportTitle(), [
            ['title' => $this->dataSource, 'columns' => $data['columns'], 'rows' => $data['rows']],
        ]);
    }

    public function exportExcel()
    {
        $data = $this->buildSections();

        return app(ReportExportService::class)->toExcel($this->exportTitle(), [
            ['title' => $this->dataSource, 'columns' => $data['columns'], 'rows' => $data['rows']],
        ]);
    }

    public function exportWord()
    {
        $data = $this->buildSections();

        return app(ReportExportService::class)->toWord($this->exportTitle(), [
            ['title' => $this->dataSource, 'columns' => $data['columns'], 'rows' => $data['rows']],
        ]);
    }

    public function exportJson()
    {
        $data = $this->buildSections();

        return app(ReportExportService::class)->toJson($this->exportTitle(), [
            ['title' => $this->dataSource, 'columns' => $data['columns'], 'rows' => $data['rows']],
        ]);
    }
}
