<?php

namespace App\Http\Controllers;

use App\Models\Settlement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettlementController extends Controller
{
    /**
     * Apply the same filter logic as ListSettlements page.
     */
    protected function queryFromRequest(Request $request)
    {
        $query = Settlement::query()
            ->orderBy('settlement_date', 'desc');

        if ($request->filled('date_from')) {
            $query->where('settlement_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('settlement_date', '<=', $request->date_to);
        }
        if ($request->filled('settlement_number')) {
            $query->where('settlement_id', 'like', '%' . $request->settlement_number . '%');
        }
        if ($request->filled('property')) {
            $query->where('truck_id', $request->property);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('quick_filter')) {
            match ($request->quick_filter) {
                'today' => $query->whereDate('settlement_date', today()),
                'week' => $query->whereBetween('settlement_date', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $query->whereMonth('settlement_date', now()->month)->whereYear('settlement_date', now()->year),
                'quarter' => $query->whereBetween('settlement_date', [now()->startOfQuarter(), now()->endOfQuarter()]),
                default => null,
            };
        }

        return $query;
    }

    /**
     * Export settlements as Excel (CSV) or PDF.
     */
    public function export(Request $request): StreamedResponse|\Illuminate\Http\Response
    {
        $type = $request->get('type', 'excel');
        $settlements = $this->queryFromRequest($request)->get();

        if ($type === 'pdf') {
            return $this->exportPdf($settlements);
        }

        return $this->exportExcel($settlements);
    }

    protected function exportExcel($settlements): StreamedResponse
    {
        $filename = 'settlements-' . Carbon::now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($settlements) {
            $out = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, [
                'Settlement ID',
                'Settlement Date',
                'Pay Period',
                'Trucks',
                'Trips',
                'Net Amount',
                'Status',
            ]);
            foreach ($settlements as $s) {
                fputcsv($out, [
                    $s->settlement_id,
                    $s->settlement_date->format('Y-m-d'),
                    $s->pay_period ?? ($s->pay_period_start?->format('M d') . ' - ' . $s->pay_period_end?->format('M d, Y')),
                    $s->trucks,
                    $s->trips,
                    $s->net_amount,
                    $s->status,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    protected function exportPdf($settlements): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('pdf.settlements', compact('settlements'));
        $filename = 'settlements-' . Carbon::now()->format('Y-m-d-His') . '.pdf';
        return $pdf->download($filename);
    }
}
