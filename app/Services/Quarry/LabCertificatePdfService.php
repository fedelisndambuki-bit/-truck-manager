<?php

namespace App\Services\Quarry;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Renders the 5 branded Laboratory & Quality Control certificate types —
 * reuses the same Barryvdh\DomPDF facade WeighbridgeTicketPdfService already
 * renders through, via a dedicated certificate template (formal
 * certificate layout with a signature block, rather than a simple ticket).
 */
class LabCertificatePdfService
{
    protected function render(string $certificateTitle, string $documentNumber, array $fields, ?string $qrPayload = null): StreamedResponse
    {
        $qrCode = $qrPayload ? QrCode::size(120)->generate($qrPayload) : null;

        $pdf = Pdf::loadView('quarry.lab-certificate', [
            'certificateTitle' => $certificateTitle,
            'documentNumber' => $documentNumber,
            'date' => now()->format('d M Y H:i'),
            'fields' => $fields,
            'qrCode' => $qrCode,
        ]);

        $content = $pdf->output();
        $filename = str()->slug($certificateTitle . '-' . $documentNumber) . '.pdf';

        return response()->streamDownload(fn () => print($content), $filename);
    }

    protected function baseFields(Certificate $certificate): array
    {
        $sample = $certificate->sample;

        return [
            ['label' => 'Certificate Type', 'value' => $certificate->type],
            ['label' => 'Customer', 'value' => $certificate->customer?->company_name],
            ['label' => 'Product', 'value' => $certificate->material?->name],
            ['label' => 'Batch Number', 'value' => $certificate->batch_number],
            ['label' => 'Sample Number', 'value' => $sample?->sample_number],
            ['label' => 'Sample Type', 'value' => $sample?->sample_type],
            ['label' => 'Test Result', 'value' => $sample?->evaluateResult()],
            ['label' => 'Status', 'value' => $certificate->status],
            ['label' => 'Issued By', 'value' => $certificate->issuedBy?->full_name],
            ['label' => 'Issued At', 'value' => $certificate->issued_at?->format('d M Y H:i')],
        ];
    }

    public function generate(Certificate $certificate): StreamedResponse
    {
        return $this->render(
            $certificate->type,
            $certificate->certificate_number,
            $this->baseFields($certificate),
            $certificate->certificate_number
        );
    }
}
