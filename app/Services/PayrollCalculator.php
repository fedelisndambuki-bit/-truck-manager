<?php

namespace App\Services;

/**
 * Kenyan statutory payroll calculator (PAYE, NSSF, SHA, Affordable Housing Levy).
 *
 * Rates reflect the Finance Act 2023 / NSSF Act 2013 (as amended) regime in force
 * for the 2025/2026 tax year. Statutory rates change periodically — the band and
 * rate constants below are the single place to update when KRA/NSSF revise them.
 */
class PayrollCalculator
{
    /** Monthly PAYE bands: [upper_limit, rate]. PHP_INT_MAX marks the top band. */
    private const PAYE_BANDS = [
        [24000, 0.10],
        [32333, 0.25],
        [500000, 0.30],
        [800000, 0.325],
        [PHP_INT_MAX, 0.35],
    ];

    private const MONTHLY_PERSONAL_RELIEF = 2400.0;

    private const NSSF_LOWER_LIMIT = 8000.0;
    private const NSSF_UPPER_LIMIT = 72000.0;
    private const NSSF_RATE = 0.06;

    private const SHA_RATE = 0.0275;
    private const SHA_MINIMUM = 300.0;

    private const HOUSING_LEVY_RATE = 0.015;

    public static function calculateNssf(float $grossPay): float
    {
        $tierOne = min($grossPay, self::NSSF_LOWER_LIMIT) * self::NSSF_RATE;

        $tierTwoBase = max(0, min($grossPay, self::NSSF_UPPER_LIMIT) - self::NSSF_LOWER_LIMIT);
        $tierTwo = $tierTwoBase * self::NSSF_RATE;

        return round($tierOne + $tierTwo, 2);
    }

    public static function calculateSha(float $grossPay): float
    {
        return round(max($grossPay * self::SHA_RATE, self::SHA_MINIMUM), 2);
    }

    public static function calculateHousingLevy(float $grossPay): float
    {
        return round($grossPay * self::HOUSING_LEVY_RATE, 2);
    }

    public static function calculatePaye(float $taxablePay): float
    {
        $remaining = $taxablePay;
        $previousLimit = 0;
        $tax = 0.0;

        foreach (self::PAYE_BANDS as [$upperLimit, $rate]) {
            if ($remaining <= 0) {
                break;
            }

            $bandWidth = $upperLimit - $previousLimit;
            $amountInBand = min($remaining, $bandWidth);
            $tax += $amountInBand * $rate;
            $remaining -= $amountInBand;
            $previousLimit = $upperLimit;
        }

        return round(max(0, $tax - self::MONTHLY_PERSONAL_RELIEF), 2);
    }

    /**
     * Full statutory breakdown for a given gross pay.
     *
     * @return array{gross_pay: float, nssf: float, sha: float, housing_levy: float, taxable_pay: float, paye: float, total_statutory_deductions: float, net_pay_before_other_deductions: float}
     */
    public static function calculate(float $grossPay): array
    {
        $nssf = self::calculateNssf($grossPay);
        $sha = self::calculateSha($grossPay);
        $housingLevy = self::calculateHousingLevy($grossPay);

        $taxablePay = max(0, $grossPay - $nssf - $sha - $housingLevy);
        $paye = self::calculatePaye($taxablePay);

        $totalStatutory = round($nssf + $sha + $housingLevy + $paye, 2);

        return [
            'gross_pay' => round($grossPay, 2),
            'nssf' => $nssf,
            'sha' => $sha,
            'housing_levy' => $housingLevy,
            'taxable_pay' => round($taxablePay, 2),
            'paye' => $paye,
            'total_statutory_deductions' => $totalStatutory,
            'net_pay_before_other_deductions' => round($grossPay - $totalStatutory, 2),
        ];
    }
}
