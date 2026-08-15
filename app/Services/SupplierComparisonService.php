<?php

namespace App\Services;

use App\Models\RequestForQuotation;
use App\Models\SupplierQuotation;
use Illuminate\Support\Collection;

/**
 * Scores and ranks the quotations submitted against one RFQ — a simple
 * weighted formula (price 40%, lead time 20%, supplier rating 25%, warranty
 * presence 15%), not a procurement-science optimizer. Each factor is
 * normalized 0–100 relative to the other quotations on the same RFQ before
 * weighting, so the comparison is always relative, never absolute.
 */
class SupplierComparisonService
{
    public function compare(RequestForQuotation $rfq): Collection
    {
        $quotations = $rfq->quotations()->with(['supplier', 'items'])->get();

        if ($quotations->isEmpty()) {
            return collect();
        }

        $minPrice = $quotations->min('total_amount');
        $maxPrice = $quotations->max('total_amount');
        $minLeadTime = $quotations->min('lead_time_days');
        $maxLeadTime = $quotations->max('lead_time_days');

        $scored = $quotations->map(function (SupplierQuotation $quotation) use ($minPrice, $maxPrice, $minLeadTime, $maxLeadTime) {
            $priceScore = $this->normalizeLowerIsBetter((float) $quotation->total_amount, (float) $minPrice, (float) $maxPrice);
            $leadTimeScore = $this->normalizeLowerIsBetter((float) ($quotation->lead_time_days ?? 0), (float) ($minLeadTime ?? 0), (float) ($maxLeadTime ?? 0));
            $ratingScore = ((float) ($quotation->supplier?->performance_rating ?? 0) / 5) * 100;
            $warrantyScore = filled($quotation->warranty) ? 100 : 0;

            $overallScore = round(
                ($priceScore * 0.40) + ($leadTimeScore * 0.20) + ($ratingScore * 0.25) + ($warrantyScore * 0.15),
                1
            );

            return [
                'quotation' => $quotation,
                'supplier_name' => $quotation->supplier?->company_name,
                'price' => (float) $quotation->total_amount,
                'lead_time_days' => $quotation->lead_time_days,
                'warranty' => $quotation->warranty,
                'delivery_terms' => $quotation->delivery_terms,
                'supplier_rating' => (float) ($quotation->supplier?->performance_rating ?? 0),
                'quality_score' => (float) ($quotation->supplier?->quality_rating ?? 0),
                'risk_rating' => $quotation->supplier?->risk_rating,
                'price_score' => round($priceScore, 1),
                'lead_time_score' => round($leadTimeScore, 1),
                'rating_score' => round($ratingScore, 1),
                'warranty_score' => $warrantyScore,
                'overall_score' => $overallScore,
            ];
        })->sortByDesc('overall_score')->values();

        return $scored->map(fn ($row, $index) => $row + ['recommended' => $index === 0]);
    }

    protected function normalizeLowerIsBetter(float $value, float $min, float $max): float
    {
        if ($max <= $min) {
            return 100.0;
        }

        return round((($max - $value) / ($max - $min)) * 100, 1);
    }
}
