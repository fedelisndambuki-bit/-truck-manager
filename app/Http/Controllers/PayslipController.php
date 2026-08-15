<?php

namespace App\Http\Controllers;

use App\Models\BrandSetting;
use App\Models\Employee;
use App\Models\Expense; // Import Expense model
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PayslipController extends Controller
{
    public function generate(Employee $employee, Request $request) // Renamed and changed argument name
    {
        // Determine the month for the payslip (default to current month)
        $payslipMonth = $request->query('month', now()->format('Y-m'));
        try {
            $periodDate = \Carbon\Carbon::parse($payslipMonth);
        } catch (\Throwable $e) {
            $periodDate = now();
        }

        $month = $periodDate->format('F Y');
        $periodLabel = strtoupper($periodDate->format('M Y'));

        $monthExpenses = Expense::query()
            ->where('employee_id', $employee->id)
            ->whereYear('date', $periodDate->year)
            ->whereMonth('date', $periodDate->month)
            ->get();

        $ytdExpenses = Expense::query()
            ->where('employee_id', $employee->id)
            ->whereYear('date', $periodDate->year)
            ->whereMonth('date', '<=', $periodDate->month)
            ->get();

        $earningsCurrent = $this->emptyEarningsTemplate();
        $earningsYtd = $this->emptyEarningsTemplate();
        $earningHoursCurrent = $this->emptyEarningsTemplate();
        $earningHoursYtd = $this->emptyEarningsTemplate();
        $earningRateTotalsCurrent = $this->emptyEarningsTemplate();
        $earningRateTotalsYtd = $this->emptyEarningsTemplate();
        $earningRateCountsCurrent = $this->emptyEarningsTemplate();
        $earningRateCountsYtd = $this->emptyEarningsTemplate();
        $deductionsCurrent = $this->emptyDeductionsTemplate();
        $deductionsYtd = $this->emptyDeductionsTemplate();

        foreach ($monthExpenses as $expense) {
            $bucket = $this->mapEarningBucket($expense);
            $earningsCurrent[$bucket] += (float) $expense->amount;
            $hours = (float) ($expense->hours ?? 0);
            $rate = (float) ($expense->rate ?? 0);
            if ($hours > 0) {
                $earningHoursCurrent[$bucket] += $hours;
            }
            if ($rate > 0) {
                $earningRateTotalsCurrent[$bucket] += $rate;
                $earningRateCountsCurrent[$bucket] += 1;
            }

            if ((filled($expense->tax_type) || filled($expense->payslip_deduction_type)) && (float) $expense->tax_amount > 0) {
                $deductionBuckets = $this->resolveDeductionBuckets($expense);
                if (! empty($deductionBuckets)) {
                    $amountPerBucket = (float) $expense->tax_amount / count($deductionBuckets);
                    foreach ($deductionBuckets as $deductionBucket) {
                        if (array_key_exists($deductionBucket, $deductionsCurrent)) {
                            $deductionsCurrent[$deductionBucket] += $amountPerBucket;
                        }
                    }
                }
            }
        }

        foreach ($ytdExpenses as $expense) {
            $bucket = $this->mapEarningBucket($expense);
            $earningsYtd[$bucket] += (float) $expense->amount;
            $hours = (float) ($expense->hours ?? 0);
            $rate = (float) ($expense->rate ?? 0);
            if ($hours > 0) {
                $earningHoursYtd[$bucket] += $hours;
            }
            if ($rate > 0) {
                $earningRateTotalsYtd[$bucket] += $rate;
                $earningRateCountsYtd[$bucket] += 1;
            }

            if ((filled($expense->tax_type) || filled($expense->payslip_deduction_type)) && (float) $expense->tax_amount > 0) {
                $deductionBuckets = $this->resolveDeductionBuckets($expense);
                if (! empty($deductionBuckets)) {
                    $amountPerBucket = (float) $expense->tax_amount / count($deductionBuckets);
                    foreach ($deductionBuckets as $deductionBucket) {
                        if (array_key_exists($deductionBucket, $deductionsYtd)) {
                            $deductionsYtd[$deductionBucket] += $amountPerBucket;
                        }
                    }
                }
            }
        }
        $earningRatesCurrent = $this->buildAverageRates($earningRateTotalsCurrent, $earningRateCountsCurrent);
        $earningRatesYtd = $this->buildAverageRates($earningRateTotalsYtd, $earningRateCountsYtd);

        $company = $employee->company;
        $brand = $company?->id
            ? BrandSetting::query()->where('company_id', $company->id)->first()
            : null;

        $payMeta = [
            'pay_date' => $periodDate->copy()->endOfMonth()->format('d/m/Y'),
            'pay_type' => 'Monthly',
            'period' => $periodLabel,
            'payroll_number' => 'EMP-' . str_pad((string) $employee->id, 6, '0', STR_PAD_LEFT),
            'ni_number' => $employee->national_id ?: '-',
            'tax_code' => $employee->tax_code ?: ($company?->tax_id ?: '-'),
            'payment_method' => (string) ($monthExpenses->first()?->payment_method ?: 'Bank Transfer'),
        ];

        $grossCurrent = array_sum($earningsCurrent);
        $grossYtd = array_sum($earningsYtd);
        $totalDeductionsCurrent = array_sum($deductionsCurrent);
        $totalDeductionsYtd = array_sum($deductionsYtd);
        $netCurrent = $grossCurrent - $totalDeductionsCurrent;
        $netYtd = $grossYtd - $totalDeductionsYtd;

        $canRenderImages = extension_loaded('gd');
        $companyLogoPath = $this->resolveCompanyLogoPath($brand?->logo_path ?: $brand?->icon_path);
        if (! $canRenderImages) {
            $companyLogoPath = null;
        }
        $companyDetails = [
            'name' => $brand?->company_name ?: ($company?->name ?: config('app.name')),
            'email' => $company?->email,
            'phone' => $company?->phone,
            'address' => $company?->address,
            'tax_id' => $company?->tax_id,
        ];

        try {
            // Load the PDF view with data
            $pdf = Pdf::loadView('pdf.payslip', compact(
                'employee',
                'month',
                'payMeta',
                'earningsCurrent',
                'earningsYtd',
                'earningHoursCurrent',
                'earningHoursYtd',
                'earningRatesCurrent',
                'earningRatesYtd',
                'deductionsCurrent',
                'deductionsYtd',
                'grossCurrent',
                'grossYtd',
                'totalDeductionsCurrent',
                'totalDeductionsYtd',
                'netCurrent',
                'netYtd',
                'companyDetails',
                'companyLogoPath',
                'canRenderImages'
            )); // Changed $driver to $employee
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'Unable to generate payslip PDF right now. Please try again later.');
        }

        $filename = "payslip-{$employee->full_name}-{$month}.pdf";

        if ($request->boolean('preview')) {
            return $pdf->stream($filename, ['Attachment' => false]);
        }

        return $pdf->download($filename);
    }

    private function emptyEarningsTemplate(): array
    {
        return [
            'standard_pay' => 0.0,
            'overtime_pay' => 0.0,
            'holiday_pay' => 0.0,
            'basic_pay' => 0.0,
            'commission_bonus' => 0.0,
            'sick_pay' => 0.0,
            'expenses' => 0.0,
        ];
    }

    private function emptyDeductionsTemplate(): array
    {
        return [
            'paye_tax' => 0.0,
            'national_insurance' => 0.0,
            'pension' => 0.0,
            'withholding_tax' => 0.0,
        ];
    }

    private function mapEarningBucket(Expense $expense): string
    {
        if (filled($expense->payslip_earning_type)) {
            return $this->normalizeEarningBucket((string) $expense->payslip_earning_type);
        }

        $normalized = strtolower(trim((string) $expense->category));

        return match ($normalized) {
            'overtime', 'overtime pay' => 'overtime_pay',
            'holiday', 'holiday pay' => 'holiday_pay',
            'salary', 'basic salary', 'basic pay' => 'basic_pay',
            'allowance', 'bonus', 'commission', 'commission and bonus' => 'commission_bonus',
            'sick', 'sick pay' => 'sick_pay',
            'expense', 'expenses' => 'expenses',
            default => 'standard_pay',
        };
    }

    private function resolveDeductionBuckets(Expense $expense): array
    {
        if (filled($expense->payslip_deduction_type)) {
            $bucket = $this->normalizeDeductionBucket((string) $expense->payslip_deduction_type);

            return $bucket ? [$bucket] : [];
        }

        $taxTypes = $this->normalizeTaxTypes($expense->tax_type);
        $buckets = [];
        foreach ($taxTypes as $taxType) {
            $bucket = $this->normalizeDeductionBucket($taxType);
            if ($bucket !== null) {
                $buckets[] = $bucket;
            }
        }

        return array_values(array_unique($buckets));
    }

    private function normalizeTaxTypes(mixed $rawTaxType): array
    {
        if (is_array($rawTaxType)) {
            return array_values(array_filter(array_map('strval', $rawTaxType)));
        }

        if (! is_string($rawTaxType) || trim($rawTaxType) === '') {
            return [];
        }

        $decoded = json_decode($rawTaxType, true);
        if (is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }

        return [$rawTaxType];
    }

    private function normalizeEarningBucket(string $raw): string
    {
        $normalized = strtolower(trim($raw));

        return match ($normalized) {
            'standard_pay', 'standard pay' => 'standard_pay',
            'overtime_pay', 'overtime pay', 'overtime' => 'overtime_pay',
            'holiday_pay', 'holiday pay', 'holiday' => 'holiday_pay',
            'basic_pay', 'basic pay', 'basic salary', 'salary' => 'basic_pay',
            'commission_bonus', 'commission and bonus', 'commission', 'bonus', 'allowance' => 'commission_bonus',
            'sick_pay', 'sick pay', 'sick' => 'sick_pay',
            'expenses', 'expense' => 'expenses',
            default => 'standard_pay',
        };
    }

    private function normalizeDeductionBucket(string $taxType): ?string
    {
        $normalized = strtolower(trim($taxType));

        if ($normalized === 'paye_tax' || $normalized === 'paye tax') {
            return 'paye_tax';
        }
        if (str_contains($normalized, 'insurance')) {
            return 'national_insurance';
        }
        if (str_contains($normalized, 'student')) {
            return null;
        }
        if (str_contains($normalized, 'pension')) {
            return 'pension';
        }
        if (str_contains($normalized, 'union')) {
            return null;
        }
        if (str_contains($normalized, 'withholding')) {
            return 'withholding_tax';
        }

        return 'paye_tax';
    }

    private function buildAverageRates(array $totals, array $counts): array
    {
        $result = [];
        foreach ($totals as $key => $value) {
            $count = (float) ($counts[$key] ?? 0);
            $result[$key] = $count > 0 ? ((float) $value / $count) : 0.0;
        }

        return $result;
    }

    private function resolveCompanyLogoPath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $normalized = ltrim($path, '/');
        if (Str::startsWith($normalized, 'storage/')) {
            $normalized = Str::after($normalized, 'storage/');
        }

        $candidate = storage_path('app/public/' . $normalized);
        if (is_file($candidate)) {
            return $candidate;
        }

        return null;
    }
}
