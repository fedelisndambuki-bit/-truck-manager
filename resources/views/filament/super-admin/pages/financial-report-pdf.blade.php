<!DOCTYPE html>
<html>
<head>
    <title>Financial Intelligence Report</title>
    <style>
        body { font-family: sans-serif; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2f4f4f; padding-bottom: 10px; }
        .header h1 { color: #2f4f4f; margin: 0; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 18px; font-weight: bold; color: #2f4f4f; margin-bottom: 10px; border-left: 4px solid #2f4f4f; padding-left: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f9fafb; font-weight: bold; }
        .kpi-grid { display: flex; flex-wrap: wrap; margin-bottom: 20px; }
        .kpi-card { width: 30%; padding: 15px; border: 1px solid #eee; margin-right: 10px; margin-bottom: 10px; }
        .kpi-label { font-size: 12px; color: #666; text-transform: uppercase; }
        .kpi-value { font-size: 18px; font-weight: bold; }
        .footer { margin-top: 50px; font-size: 10px; text-align: center; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Financial Intelligence Report</h1>
        <p>Generated on {{ now()->format('F d, Y') }}</p>
    </div>

    <div class="section">
        <div class="section-title">Core Performance KPI</div>
        <table>
            <tr>
                <th>Metric</th>
                <th>Current Value</th>
            </tr>
            <tr>
                <td>Total MRR</td>
                <td>KES {{ number_format($mrr, 2) }}</td>
            </tr>
            <tr>
                <td>Annual Run Rate (ARR)</td>
                <td>KES {{ number_format($arr, 2) }}</td>
            </tr>
            <tr>
                <td>Gross Margin</td>
                <td>{{ $grossMargin }}%</td>
            </tr>
            <tr>
                <td>Net Profit (Monthly)</td>
                <td>KES {{ number_format($netProfit, 2) }}</td>
            </tr>
            <tr>
                <td>Active Vehicles</td>
                <td>{{ $activeVehicles }}</td>
            </tr>
            <tr>
                <td>Financial Health Score</td>
                <td>{{ $healthScore }}/100</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Revenue & Unit Economics</div>
        <table>
            <tr>
                <td>ARPU (Avg Revenue Per User)</td>
                <td>KES {{ number_format($arpu, 2) }}</td>
            </tr>
            <tr>
                <td>Churn Rate</td>
                <td>{{ $churnRate }}%</td>
            </tr>
            <tr>
                <td>LTV (Lifetime Value)</td>
                <td>KES {{ number_format($ltv, 2) }}</td>
            </tr>
            <tr>
                <td>CAC (Acquisition Cost)</td>
                <td>KES {{ number_format($cac, 2) }}</td>
            </tr>
            <tr>
                <td>LTV/CAC Ratio</td>
                <td>{{ $ltvCacRatio }}x</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Strategic Outlook</div>
        <p><strong>Cash Runway:</strong> {{ $cashRunway }} Months</p>
        <p><strong>Break-even Progress:</strong> {{ $breakEvenProgress }}%</p>
        <p><strong>Status:</strong> 
            @if($healthScore > 80)
                Platform is in excellent financial health. Expansion ready.
            @elseif($healthScore > 50)
                Platform is stable but optimization is recommended.
            @else
                Critical attention required on margins and churn.
            @endif
        </p>
    </div>

    <div class="footer">
        Confidential Board Report - Internal Use Only
    </div>
</body>
</html>
