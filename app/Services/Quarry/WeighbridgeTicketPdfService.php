<?php

namespace App\Services\Quarry;

use App\Models\DispatchOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Renders the 7 branded single-record Smart Weighbridge documents (Tare/
 * Loading/Gross/Dispatch tickets, Delivery Note, Gate Pass, Certificate of
 * Weight) — reuses the same Barryvdh\DomPDF facade ReportExportService
 * already renders through, via a dedicated ticket template rather than
 * ReportExportService's tabular sections+KPIs shape (these are branded
 * single-record documents, not tabular reports).
 */
class WeighbridgeTicketPdfService
{
    protected function render(string $ticketTitle, string $documentNumber, array $fields, ?string $qrPayload = null): StreamedResponse
    {
        $qrCode = $qrPayload ? QrCode::size(120)->generate($qrPayload) : null;

        $pdf = Pdf::loadView('weighbridge.ticket', [
            'ticketTitle' => $ticketTitle,
            'documentNumber' => $documentNumber,
            'date' => now()->format('d M Y H:i'),
            'fields' => $fields,
            'qrCode' => $qrCode,
        ]);

        $content = $pdf->output();
        $filename = str()->slug($ticketTitle . '-' . $documentNumber) . '.pdf';

        return response()->streamDownload(fn () => print($content), $filename);
    }

    public function tareTicket(DispatchOrder $order): StreamedResponse
    {
        return $this->render('Weighbridge Ticket — Tare', $order->dispatch_number, [
            ['label' => 'Vehicle Registration', 'value' => $order->vehicle_registration],
            ['label' => 'Trailer', 'value' => $order->trailer_number],
            ['label' => 'Driver', 'value' => $order->driver_name],
            ['label' => 'Customer', 'value' => $order->customer?->company_name],
            ['label' => 'Weighbridge', 'value' => $order->weighbridge?->name],
            ['label' => 'Tare Weight', 'value' => $order->tare_weight ? $order->tare_weight . ' t' : null],
            ['label' => 'Recorded At', 'value' => $order->tare_weighed_at?->format('d M Y H:i')],
            ['label' => 'Operator', 'value' => $order->tareOperator?->full_name],
        ], $order->dispatch_number);
    }

    public function loadingTicket(DispatchOrder $order): StreamedResponse
    {
        $instruction = $order->currentLoadingInstruction();

        return $this->render('Digital Loading Ticket', $instruction?->loading_number ?? $order->dispatch_number, [
            ['label' => 'Dispatch Number', 'value' => $order->dispatch_number],
            ['label' => 'Customer', 'value' => $order->customer?->company_name],
            ['label' => 'Truck', 'value' => $order->vehicle_registration],
            ['label' => 'Driver', 'value' => $order->driver_name],
            ['label' => 'Product', 'value' => $order->material?->name],
            ['label' => 'Stockpile', 'value' => $order->stockpile?->label],
            ['label' => 'Loading Bay', 'value' => $order->loadingBay?->name],
            ['label' => 'Target Net Weight', 'value' => $order->target_net_weight ? $order->target_net_weight . ' t' : null],
            ['label' => 'Tare Weight', 'value' => $order->tare_weight ? $order->tare_weight . ' t' : null],
            ['label' => 'Required Gross Weight', 'value' => $order->required_gross_weight ? $order->required_gross_weight . ' t' : null],
            ['label' => 'Tolerance', 'value' => $order->tolerance_tonnes ? '±' . $order->tolerance_tonnes . ' t' : null],
            ['label' => 'Operator', 'value' => $instruction?->loaderOperator?->full_name],
        ], $instruction?->loading_number ?? $order->dispatch_number);
    }

    public function grossTicket(DispatchOrder $order): StreamedResponse
    {
        return $this->render('Weighbridge Ticket — Gross', $order->dispatch_number, [
            ['label' => 'Vehicle Registration', 'value' => $order->vehicle_registration],
            ['label' => 'Customer', 'value' => $order->customer?->company_name],
            ['label' => 'Product', 'value' => $order->material?->name],
            ['label' => 'Tare Weight', 'value' => $order->tare_weight ? $order->tare_weight . ' t' : null],
            ['label' => 'Gross Weight', 'value' => $order->gross_weight ? $order->gross_weight . ' t' : null],
            ['label' => 'Net Weight', 'value' => $order->net_weight ? $order->net_weight . ' t' : null],
            ['label' => 'Variance', 'value' => $order->variance !== null ? $order->variance . ' t (' . $order->variance_percent . '%)' : null],
            ['label' => 'Weight Status', 'value' => $order->weight_status],
            ['label' => 'Recorded At', 'value' => $order->gross_weighed_at?->format('d M Y H:i')],
            ['label' => 'Operator', 'value' => $order->grossOperator?->full_name],
        ], $order->dispatch_number);
    }

    public function dispatchTicket(DispatchOrder $order): StreamedResponse
    {
        return $this->render('Dispatch Ticket', $order->dispatch_number, [
            ['label' => 'Order Number', 'value' => $order->order_number],
            ['label' => 'Customer', 'value' => $order->customer?->company_name],
            ['label' => 'Transport Company', 'value' => $order->transportCompany?->name],
            ['label' => 'Vehicle', 'value' => $order->vehicle_registration],
            ['label' => 'Driver', 'value' => $order->driver_name],
            ['label' => 'Product', 'value' => $order->material?->name],
            ['label' => 'Net Weight', 'value' => $order->net_weight ? $order->net_weight . ' t' : null],
            ['label' => 'Status', 'value' => $order->status],
            ['label' => 'Invoice', 'value' => $order->invoice?->invoice_number],
        ], $order->dispatch_number);
    }

    public function deliveryNote(DispatchOrder $order): StreamedResponse
    {
        $pod = $order->proofOfDelivery;

        return $this->render('Delivery Note', $order->dispatch_number, [
            ['label' => 'Customer', 'value' => $order->customer?->company_name],
            ['label' => 'Vehicle', 'value' => $order->vehicle_registration],
            ['label' => 'Product', 'value' => $order->material?->name],
            ['label' => 'Delivered Quantity', 'value' => ($pod?->delivered_quantity ?? $order->net_weight) . ' t'],
            ['label' => 'Delivery Time', 'value' => $pod?->delivery_time?->format('d M Y H:i')],
            ['label' => 'Receiver', 'value' => $pod?->receiver_name],
            ['label' => 'Delivery Status', 'value' => $pod?->status],
        ], $order->dispatch_number);
    }

    public function gatePass(DispatchOrder $order): StreamedResponse
    {
        return $this->render('Gate Pass', $order->dispatch_number, [
            ['label' => 'Vehicle', 'value' => $order->vehicle_registration],
            ['label' => 'Driver', 'value' => $order->driver_name],
            ['label' => 'Customer', 'value' => $order->customer?->company_name],
            ['label' => 'Gate In', 'value' => $order->gate_in_at?->format('d M Y H:i') . ' (' . ($order->gate ?? '—') . ')'],
            ['label' => 'Gate Out', 'value' => $order->exit_at?->format('d M Y H:i') . ' (' . ($order->exit_gate ?? '—') . ')'],
            ['label' => 'Security Clearance', 'value' => $order->securityClearanceBy?->full_name],
            ['label' => 'Status', 'value' => $order->status],
        ], $order->dispatch_number);
    }

    public function certificateOfWeight(DispatchOrder $order): StreamedResponse
    {
        return $this->render('Certificate of Weight', $order->dispatch_number, [
            ['label' => 'Vehicle', 'value' => $order->vehicle_registration],
            ['label' => 'Product', 'value' => $order->material?->name],
            ['label' => 'Tare Weight', 'value' => $order->tare_weight ? $order->tare_weight . ' t' : null],
            ['label' => 'Gross Weight', 'value' => $order->gross_weight ? $order->gross_weight . ' t' : null],
            ['label' => 'Net Weight', 'value' => $order->net_weight ? $order->net_weight . ' t' : null],
            ['label' => 'Weighbridge', 'value' => $order->weighbridge?->name],
            ['label' => 'Certified Accurate', 'value' => $order->weight_status === 'Within Tolerance' ? 'Yes' : 'Subject to Review'],
        ], $order->dispatch_number);
    }
}
