<?php

namespace App\Services;

use App\Models\SupplierInvoice;

/**
 * Purchase Order -> Goods Received Note -> Supplier Invoice three-way match.
 * A pragmatic tolerance-based comparison (not a full reconciliation engine):
 * quantities must match within 1%, amounts within the same tolerance of the
 * PO total. Discrepancies are recorded, never silently ignored.
 */
class ThreeWayMatchService
{
    protected float $toleranceFraction = 0.01;

    public function match(SupplierInvoice $invoice): array
    {
        $discrepancies = [];

        $po = $invoice->purchaseOrder;
        $grn = $invoice->goodsReceivedNote;

        if (! $po) {
            $discrepancies[] = 'No Purchase Order linked — cannot verify against ordered quantities/prices.';
        } else {
            $poTotal = (float) $po->total_amount;
            $invoiceTotal = (float) $invoice->total_amount;

            if ($poTotal > 0 && abs($invoiceTotal - $poTotal) > ($poTotal * $this->toleranceFraction)) {
                $discrepancies[] = sprintf(
                    'Invoice total (%s) differs from Purchase Order total (%s) by more than %d%%.',
                    number_format($invoiceTotal, 2),
                    number_format($poTotal, 2),
                    $this->toleranceFraction * 100
                );
            }
        }

        if (! $grn) {
            $discrepancies[] = 'No Goods Received Note linked — cannot verify received quantities.';
        } else {
            $invoicedQuantity = (float) $invoice->items()->sum('quantity');
            $acceptedQuantity = (float) $grn->accepted_quantity;

            if ($acceptedQuantity > 0 && abs($invoicedQuantity - $acceptedQuantity) > ($acceptedQuantity * $this->toleranceFraction)) {
                $discrepancies[] = sprintf(
                    'Invoiced quantity (%s) differs from GRN accepted quantity (%s) by more than %d%%.',
                    number_format($invoicedQuantity, 2),
                    number_format($acceptedQuantity, 2),
                    $this->toleranceFraction * 100
                );
            }

            if (! $grn->quality_approved) {
                $discrepancies[] = 'Goods Received Note has not passed quality inspection yet.';
            }
        }

        $invoice->update([
            'matching_status' => empty($discrepancies) ? 'Matched' : 'Discrepancy',
            'match_discrepancies' => $discrepancies,
        ]);

        return $discrepancies;
    }

    /**
     * Post the invoice to Accounts Payable — only once matched and approved.
     */
    public function postApproved(SupplierInvoice $invoice): void
    {
        app(\App\Services\PostingEngineService::class)->post('supplier_invoice.approved', [
            'company_id' => $invoice->company_id,
            'reference_type' => 'supplier_invoice',
            'reference_id' => (string) $invoice->id,
            'amount' => (float) $invoice->total_amount,
            'description' => "Supplier invoice {$invoice->invoice_number} — {$invoice->supplier?->company_name}",
        ]);

        $invoice->update(['status' => 'Approved']);
    }
}
