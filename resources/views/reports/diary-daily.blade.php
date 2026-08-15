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
        .stat-box { width: 22%; display: inline-block; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; text-align: center; border-radius: 8px; margin-right: 6px; }
        .stat-value { font-size: 18px; font-weight: bold; color: #2f4f4f; }
        .stat-label { font-size: 9px; color: #64748b; text-transform: uppercase; margin-top: 5px; }
        h2 { color: #2f4f4f; border-left: 4px solid #2f4f4f; padding-left: 10px; margin-top: 20px; margin-bottom: 12px; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #2f4f4f; color: white; text-align: left; padding: 8px; text-transform: uppercase; font-size: 10px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #fcfcfc; }
        .badge { padding: 2px 6px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .badge-draft, .badge-locked { background: #f1f5f9; color: #475569; }
        .badge-submitted { background: #fef3c7; color: #d97706; }
        .badge-reviewed { background: #dbeafe; color: #2563eb; }
        .badge-approved { background: #dcfce7; color: #16a34a; }
        .badge-rejected { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — Generated on {{ $date }}</p>
    </div>

    @foreach ($diaries as $diary)
        <h2>{{ $diary->diary_number }} — {{ $diary->site?->name ?? '—' }} — {{ $diary->date?->format('d M Y') }}</h2>

        <div class="stats-container">
            <div class="stat-box"><div class="stat-value">{{ $diary->status }}</div><div class="stat-label">Status</div></div>
            <div class="stat-box"><div class="stat-value">{{ $diary->workers_on_site }}</div><div class="stat-label">Workers Present</div></div>
            <div class="stat-box"><div class="stat-value">{{ $diary->equipment_active_count }}</div><div class="stat-label">Equipment Active</div></div>
            <div class="stat-box"><div class="stat-value">{{ $diary->progress_pct ?? 0 }}%</div><div class="stat-label">Progress</div></div>
        </div>

        <table class="kv">
            <tr><td style="width:25%;color:#64748b;">Project</td><td>{{ $diary->project?->name ?? '—' }}</td></tr>
            <tr><td style="color:#64748b;">Supervisor</td><td>{{ $diary->loggedBy?->full_name ?? '—' }}</td></tr>
            <tr><td style="color:#64748b;">Shift / Phase</td><td>{{ $diary->shift }} / {{ $diary->phase ?? '—' }}</td></tr>
            <tr><td style="color:#64748b;">Weather</td><td>{{ $diary->conditions ?? '—' }}, {{ $diary->temperature_high ?? '—' }}°C, rainfall {{ $diary->rainfall_mm ?? 0 }}mm</td></tr>
        </table>

        <h2>Activities</h2>
        <table>
            <thead><tr><th>Task</th><th>Area</th><th>Completion %</th><th>Comments</th></tr></thead>
            <tbody>
                @forelse ($diary->activities as $activity)
                    <tr><td>{{ $activity->task }}</td><td>{{ $activity->area ?? '—' }}</td><td>{{ $activity->completion_pct }}%</td><td>{{ $activity->comments ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="4">No activities recorded.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>Delays</h2>
        <table>
            <thead><tr><th>Type</th><th>Reason</th><th>Duration</th><th>Impact</th></tr></thead>
            <tbody>
                @forelse ($diary->delays as $delay)
                    <tr><td>{{ $delay->type }}</td><td>{{ $delay->reason }}</td><td>{{ $delay->duration_hours }} hrs</td><td>{{ $delay->impact }}</td></tr>
                @empty
                    <tr><td colspan="4">No delays recorded.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>Visitors</h2>
        <table>
            <thead><tr><th>Visitor</th><th>Company</th><th>Purpose</th><th>Check In</th><th>Check Out</th></tr></thead>
            <tbody>
                @forelse ($diary->diaryVisitors as $visitor)
                    <tr><td>{{ $visitor->visitor_name }}</td><td>{{ $visitor->company ?? '—' }}</td><td>{{ $visitor->purpose ?? '—' }}</td><td>{{ $visitor->check_in?->format('H:i') ?? '—' }}</td><td>{{ $visitor->check_out?->format('H:i') ?? '—' }}</td></tr>
                @empty
                    <tr><td colspan="5">No visitors recorded.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if ($diary->general_observations || $diary->challenges || $diary->risks)
            <h2>Supervisor Notes</h2>
            <table class="kv">
                <tr><td style="width:25%;color:#64748b;">General Observations</td><td>{{ $diary->general_observations ?? '—' }}</td></tr>
                <tr><td style="color:#64748b;">Challenges</td><td>{{ $diary->challenges ?? '—' }}</td></tr>
                <tr><td style="color:#64748b;">Risks</td><td>{{ $diary->risks ?? '—' }}</td></tr>
                <tr><td style="color:#64748b;">Next Day Plan</td><td>{{ $diary->next_day_plan ?? '—' }}</td></tr>
            </table>
        @endif
    @endforeach
</body>
</html>
