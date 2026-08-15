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
            <div class="stat-value">{{ $project->status }}</div>
            <div class="stat-label">Status</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $project->progress_percent }}%</div>
            <div class="stat-label">Progress</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $currency }} {{ number_format($project->budget ?? 0, 0) }}</div>
            <div class="stat-label">Budget</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $currency }} {{ number_format($project->contract_value ?? 0, 0) }}</div>
            <div class="stat-label">Contract Value</div>
        </div>
    </div>

    <h2>Project Details</h2>
    <table class="kv">
        <tr><td>Project Code</td><td>{{ $project->project_code }}</td></tr>
        <tr><td>Category</td><td>{{ $project->category ?? '—' }}</td></tr>
        <tr><td>Client</td><td>{{ $project->client?->company_name ?? '—' }}</td></tr>
        <tr><td>Project Manager</td><td>{{ $project->projectManager?->full_name ?? '—' }}</td></tr>
        <tr><td>Location</td><td>{{ $project->site_address ?? '—' }}</td></tr>
        <tr><td>Priority</td><td>{{ $project->priority ?? '—' }}</td></tr>
        <tr><td>Risk Level</td><td>{{ $project->risk_level ?? '—' }}</td></tr>
        <tr><td>Start Date</td><td>{{ $project->start_date?->format('d M Y') ?? '—' }}</td></tr>
        <tr><td>Expected Completion</td><td>{{ $project->end_date?->format('d M Y') ?? '—' }}</td></tr>
        <tr><td>Actual Completion</td><td>{{ $project->actual_completion_date?->format('d M Y') ?? '—' }}</td></tr>
    </table>

    <h2>Sites</h2>
    <table>
        <thead><tr><th>Name</th><th>Location</th><th>Site Manager</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($project->sites as $site)
                <tr>
                    <td>{{ $site->name }}</td>
                    <td>{{ $site->location ?? '—' }}</td>
                    <td>{{ $site->siteManager?->full_name ?? '—' }}</td>
                    <td>{{ $site->status }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No sites recorded.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Project Team</h2>
    <table>
        <thead><tr><th>Role</th><th>Employee</th></tr></thead>
        <tbody>
            @forelse ($project->teamMembers as $member)
                <tr>
                    <td>{{ $member->role_slot }}</td>
                    <td>{{ $member->employee?->full_name ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No team assigned.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
