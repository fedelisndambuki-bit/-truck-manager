<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    protected function queryFromRequest(Request $request)
    {
        $query = Invoice::query()
            ->with(['client', 'truck', 'driver'])
            ->orderBy('invoice_date', 'desc');

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        if ($request->filled('invoice_number')) {
            $query->where('invoice_number', 'like', '%' . $request->invoice_number . '%');
        }

        if ($request->filled('property')) {
            $query->where('truck_id', $request->property);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('quick_filter')) {
            match ($request->quick_filter) {
                'today' => $query->whereDate('invoice_date', today()),
                'week' => $query->whereBetween('invoice_date', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $query->whereMonth('invoice_date', now()->month)->whereYear('invoice_date', now()->year),
                'quarter' => $query->whereBetween('invoice_date', [now()->startOfQuarter(), now()->endOfQuarter()]),
                default => null,
            };
        }

        return $query;
    }

    public function export(Request $request): StreamedResponse|\Illuminate\Http\Response
    {
        $type = strtolower((string) $request->get('type', 'excel'));
        $invoices = $this->queryFromRequest($request)->get();

        if ($type === 'pdf') {
            return $this->exportPdf($invoices);
        }

        return $this->exportExcel($invoices);
    }

    protected function exportExcel($invoices): StreamedResponse
    {
        $filename = 'invoices-' . Carbon::now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($invoices) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, [
                'Invoice Number',
                'Invoice Date',
                'Due Date',
                'Client',
                'Truck',
                'Driver',
                'Route',
                'Subtotal',
                'Tax',
                'Total Amount',
                'Status',
            ]);

            foreach ($invoices as $invoice) {
                fputcsv($out, [
                    $invoice->invoice_number,
                    optional($invoice->invoice_date)->format('Y-m-d'),
                    optional($invoice->due_date)->format('Y-m-d'),
                    optional($invoice->client)->company_name ?? optional($invoice->client)->name,
                    optional($invoice->truck)->plate_number,
                    optional($invoice->driver)->full_name,
                    trim(($invoice->route_from ?? '') . ' -> ' . ($invoice->route_to ?? '')),
                    $invoice->subtotal,
                    $invoice->tax,
                    $invoice->total_amount,
                    $invoice->status,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    protected function exportPdf($invoices): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView('pdf.invoices-export', compact('invoices'));
        $filename = 'invoices-' . Carbon::now()->format('Y-m-d-His') . '.pdf';

        return $pdf->download($filename);
    }

    public function generatePdf(Invoice $invoice)
    {
        // Assuming you have a 'Company' model or config for company details
        // For now, let's use a placeholder or retrieve from config/env
        $company = (object)[
            'name' => 'Truck Manager Inc.',
            'address' => '123 Trucking Road, Transport City',
            'phone' => '+1 (555) 123-4567',
            'email' => 'info@truckmanager.com',
            'company_logo' => 'company_logos/truck_manager_logo.png' // Placeholder
        ];

        // Ensure logo exists, otherwise use a placeholder
        $companyLogoPath = public_path('storage/' . $company->company_logo);
        if (!Storage::disk('public')->exists($company->company_logo)) {
            $companyLogoPath = null; // No logo or use a default placeholder
        }


        $pdf = Pdf::loadView('pdf.invoice', compact('invoice', 'company', 'companyLogoPath'));

        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }
}
