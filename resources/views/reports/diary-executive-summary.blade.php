<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2f4f4f; padding-bottom: 10px; }
        .header h1 { color: #2f4f4f; margin: 0; text-transform: uppercase; }
        .header p { margin: 5px 0; color: #666; }
        .stats-container { width: 100%; margin-bottom: 25px; }
        .stat-box { width: 22%; display: inline-block; background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px; text-align: center; border-radius: 8px; margin: 0 1% 12px 0; }
        .stat-value { font-size: 22px; font-weight: bold; color: #2f4f4f; }
        .stat-label { font-size: 9px; color: #64748b; text-transform: uppercase; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — Generated on {{ $date }} (last 30 days)</p>
    </div>

    <div class="stats-container">
        <div class="stat-box"><div class="stat-value">{{ $summary['total_diaries'] }}</div><div class="stat-label">Total Diaries</div></div>
        <div class="stat-box"><div class="stat-value">{{ $summary['approved'] }}</div><div class="stat-label">Approved</div></div>
        <div class="stat-box"><div class="stat-value">{{ $summary['pending'] }}</div><div class="stat-label">Pending</div></div>
        <div class="stat-box"><div class="stat-value">{{ $summary['rejected'] }}</div><div class="stat-label">Rejected</div></div>
        <div class="stat-box"><div class="stat-value">{{ $summary['avg_progress'] }}%</div><div class="stat-label">Avg. Progress</div></div>
        <div class="stat-box"><div class="stat-value">{{ $summary['avg_workforce'] }}</div><div class="stat-label">Avg. Workforce/Day</div></div>
        <div class="stat-box"><div class="stat-value">{{ $summary['total_deliveries'] }}</div><div class="stat-label">Material Deliveries</div></div>
        <div class="stat-box"><div class="stat-value">{{ $summary['total_shortages'] }}</div><div class="stat-label">Material Shortages</div></div>
        <div class="stat-box"><div class="stat-value">{{ $summary['open_delays'] }}</div><div class="stat-label">Open Delays</div></div>
        <div class="stat-box"><div class="stat-value">{{ $summary['severe_weather_days'] }}</div><div class="stat-label">Severe Weather Days</div></div>
    </div>
</body>
</html>
