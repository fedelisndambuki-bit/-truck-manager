<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateExpense extends CreateRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->normalizeTaxes($data);
    }

    private function normalizeTaxes(array $data): array
    {
        $selected = $data['tax_type'] ?? [];
        $selected = is_array($selected) ? array_values(array_filter(array_map('strval', $selected))) : [];
        $rows = $data['tax_breakdown'] ?? [];
        $rows = is_array($rows) ? $rows : [];
        $baseAmount = (float) ($data['amount'] ?? 0);
        $totalTax = 0.0;
        $normalizedRows = [];

        foreach ($rows as $row) {
            $taxName = trim((string) ($row['tax_name'] ?? ''));
            if ($taxName === '' || ! in_array($taxName, $selected, true)) {
                continue;
            }

            $percentage = (float) ($row['tax_percentage'] ?? ($row['vat_percentage'] ?? 0));
            $isPercentageTax = in_array(strtolower($taxName), ['vat', 'withholding tax']);
            
            $amount = $isPercentageTax
                ? (($baseAmount * $percentage) / 100)
                : (float) ($row['amount'] ?? 0);

            $amount = max(0, round($amount, 2));
            $totalTax += $amount;

            $normalizedRows[] = [
                'tax_name' => $taxName,
                'amount' => $amount,
                'tax_percentage' => $isPercentageTax ? $percentage : null,
            ];
        }

        $data['tax_type'] = empty($selected) ? null : json_encode($selected);
        $data['tax_amount'] = empty($selected) ? null : round($totalTax, 2);
        $data['tax_breakdown'] = empty($normalizedRows) ? null : $normalizedRows;

        return $data;
    }
}
