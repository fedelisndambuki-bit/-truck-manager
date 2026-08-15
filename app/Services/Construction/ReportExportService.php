<?php

namespace App\Services\Construction;

use App\Models\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * One shared multi-format exporter, reused by Data Exports, Scheduled
 * Reports, and Custom Reports — takes the same title/kpis/sections(columns,
 * rows) shape already established by `reports.project-finance-report`
 * (reused a third time here, unmodified) so every report in the app can be
 * exported to PDF, CSV, Excel, Word, or JSON without bespoke per-report code.
 *
 * PowerPoint is intentionally not supported — see the BI module plan for why.
 */
class ReportExportService
{
    /**
     * $companyId/$generatedById let CLI callers (e.g. the scheduled-report
     * dispatcher, which runs with no authenticated user) log exports against
     * the correct company — HTTP callers omit them and fall back to Auth::.
     */
    public function toPdf(string $title, array $sections, array $kpis = [], ?int $companyId = null, ?int $generatedById = null): StreamedResponse
    {
        $pdf = Pdf::loadView('reports.project-finance-report', [
            'title' => $title,
            'date' => now()->format('d M Y'),
            'kpis' => $kpis,
            'sections' => $sections,
        ]);

        $content = $pdf->output();
        $this->logExport($title, 'pdf', strlen($content), $companyId, $generatedById);

        return response()->streamDownload(fn () => print ($content), $this->filename($title, 'pdf'));
    }

    public function pdfContent(string $title, array $sections, array $kpis = []): string
    {
        return Pdf::loadView('reports.project-finance-report', [
            'title' => $title,
            'date' => now()->format('d M Y'),
            'kpis' => $kpis,
            'sections' => $sections,
        ])->output();
    }

    public function toCsv(string $title, array $sections, ?int $companyId = null, ?int $generatedById = null): StreamedResponse
    {
        $this->logExport($title, 'csv', null, $companyId, $generatedById);

        return response()->streamDownload(function () use ($sections, $title) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [$title]);
            fputcsv($out, ['Generated on ' . now()->format('d M Y H:i')]);

            foreach ($sections as $section) {
                fputcsv($out, []);
                if (! empty($section['title'])) {
                    fputcsv($out, [$section['title']]);
                }
                fputcsv($out, $section['columns']);
                foreach ($section['rows'] as $row) {
                    fputcsv($out, $row);
                }
            }

            fclose($out);
        }, $this->filename($title, 'csv'), ['Content-Type' => 'text/csv']);
    }

    public function toJson(string $title, array $sections, array $kpis = [], ?int $companyId = null, ?int $generatedById = null): StreamedResponse
    {
        $payload = json_encode([
            'title' => $title,
            'generated_at' => now()->toIso8601String(),
            'kpis' => $kpis,
            'sections' => $sections,
        ], JSON_PRETTY_PRINT);

        $this->logExport($title, 'json', strlen($payload), $companyId, $generatedById);

        return response()->streamDownload(fn () => print ($payload), $this->filename($title, 'json'), ['Content-Type' => 'application/json']);
    }

    public function toExcel(string $title, array $sections, ?int $companyId = null, ?int $generatedById = null): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($sections as $index => $section) {
            $rawTitle = $section['title'] ?? ('Sheet ' . ($index + 1));
            $sheetTitle = str(preg_replace('/[:\\\\\/?*\[\]]/', '', $rawTitle))->limit(28, '')->toString();
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($sheetTitle ?: ('Sheet ' . ($index + 1)));

            $sheet->fromArray($section['columns'], null, 'A1');
            $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

            $rowIndex = 2;
            foreach ($section['rows'] as $row) {
                $sheet->fromArray($row, null, 'A' . $rowIndex);
                $rowIndex++;
            }

            foreach (range('A', $sheet->getHighestColumn()) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        if ($spreadsheet->getSheetCount() === 0) {
            $spreadsheet->createSheet()->setTitle('Report');
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $filename = $this->filename($title, 'xlsx');

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $this->logExport($title, 'excel', strlen($content), $companyId, $generatedById);

        return response()->streamDownload(fn () => print ($content), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function toWord(string $title, array $sections, ?int $companyId = null, ?int $generatedById = null): StreamedResponse
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addTitle($title, 1);
        $section->addText('Generated on ' . now()->format('d M Y H:i'));

        foreach ($sections as $reportSection) {
            if (! empty($reportSection['title'])) {
                $section->addTitle($reportSection['title'], 2);
            }

            $table = $section->addTable(['borderSize' => 4, 'borderColor' => 'cccccc', 'cellMargin' => 60]);

            $table->addRow();
            foreach ($reportSection['columns'] as $column) {
                $table->addCell(2500)->addText((string) $column, ['bold' => true]);
            }

            foreach ($reportSection['rows'] as $row) {
                $table->addRow();
                foreach ($row as $cell) {
                    $table->addCell(2500)->addText((string) $cell);
                }
            }
        }

        $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
        $filename = $this->filename($title, 'docx');

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        $this->logExport($title, 'word', strlen($content), $companyId, $generatedById);

        return response()->streamDownload(fn () => print ($content), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    protected function filename(string $title, string $extension): string
    {
        return str($title)->slug()->toString() . '-' . now()->format('Y-m-d') . '.' . $extension;
    }

    protected function logExport(string $title, string $format, ?int $fileSize, ?int $companyId = null, ?int $generatedById = null): void
    {
        ReportExport::create([
            'company_id' => $companyId ?? Auth::user()?->company_id,
            'report_name' => $title,
            'format' => $format,
            'generated_by_id' => $generatedById ?? Auth::id(),
            'file_size' => $fileSize,
        ]);
    }
}
