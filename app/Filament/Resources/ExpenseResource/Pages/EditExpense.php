<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExpense extends EditRecord
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $selected = $data['tax_type'] ?? null;
        if (is_string($selected) && $selected !== '') {
            $decoded = json_decode($selected, true);
            $selected = is_array($decoded) ? $decoded : [$selected];
        } elseif (! is_array($selected)) {
            $selected = [];
        }

        if (empty($data['tax_breakdown']) && ! empty($selected)) {
            $taxAmount = (float) ($data['tax_amount'] ?? 0);
            $perTax = count($selected) > 0 ? ($taxAmount / count($selected)) : 0;
            $data['tax_breakdown'] = array_map(
                fn ($tax) => [
                    'tax_name' => (string) $tax,
                    'amount' => round($perTax, 2),
                    'tax_percentage' => in_array(strtolower((string) $tax), ['vat', 'withholding tax']) ? 0 : null,
                ],
                $selected
            );
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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
