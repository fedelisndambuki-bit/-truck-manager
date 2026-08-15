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
        .kv { width: 100%; }
        .kv td { border: none; padding: 4px 8px; }
        .kv td:first-child { color: #64748b; width: 40%; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — Generated on {{ $date }}</p>
    </div>

    <div class="stats-container">
        <div class="stat-box">
            <div class="stat-value">{{ $site->status }}</div>
            <div class="stat-label">Status</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $site->progress_percent }}%</div>
            <div class="stat-label">Progress</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $site->current_phase ?? '—' }}</div>
            <div class="stat-label">Phase</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $site->safety_risk_level ?? '—' }}</div>
            <div class="stat-label">Risk Level</div>
        </div>
    </div>

    <h2>Site Details</h2>
    <table class="kv">
        <tr><td>Site Code</td><td>{{ $site->site_code }}</td></tr>
        <tr><td>Project</td><td>{{ $site->project?->name ?? '—' }}</td></tr>
        <tr><td>Location</td><td>{{ $site->location ?? '—' }}</td></tr>
        <tr><td>County</td><td>{{ $site->county ?? '—' }}</td></tr>
        <tr><td>Site Manager</td><td>{{ $site->teamSlot('Site Manager')?->full_name ?? $site->siteManager?->full_name ?? '—' }}</td></tr>
        <tr><td>Start Date</td><td>{{ $site->start_date?->format('d M Y') ?? '—' }}</td></tr>
        <tr><td>Expected Completion</td><td>{{ $site->expected_completion_date?->format('d M Y') ?? '—' }}</td></tr>
    </table>

    <h2>Site Team</h2>
    <table>
        <thead><tr><th>Role</th><th>Employee</th></tr></thead>
        <tbody>
            @forelse ($site->teamMembers as $member)
                <tr><td>{{ $member->role_slot }}</td><td>{{ $member->employee?->full_name ?? '—' }}</td></tr>
            @empty
                <tr><td colspan="2">No team assigned.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Recent Safety Incidents</h2>
    <table>
        <thead><tr><th>Date</th><th>Severity</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($site->incidents()->latest('date')->limit(10)->get() as $incident)
                <tr><td>{{ $incident->date?->format('d M Y') }}</td><td>{{ $incident->severity }}</td><td>{{ $incident->status }}</td></tr>
            @empty
                <tr><td colspan="3">No incidents recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
