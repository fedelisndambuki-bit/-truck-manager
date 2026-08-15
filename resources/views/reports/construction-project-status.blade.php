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
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #2f4f4f; color: white; text-align: left; padding: 8px; text-transform: uppercase; font-size: 10px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #fcfcfc; }
        .badge { padding: 2px 6px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .badge-active { background: #dcfce7; color: #16a34a; }
        .badge-planning { background: #f1f5f9; color: #475569; }
        .badge-delayed { background: #fee2e2; color: #dc2626; }
        .badge-onhold { background: #fef3c7; color: #d97706; }
        .badge-completed { background: #dbeafe; color: #2563eb; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — Generated on {{ $date }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Project</th>
                <th>Client</th>
                <th>Project Manager</th>
                <th>Status</th>
                <th>Progress</th>
                <th>Budget</th>
                <th>Contract Value</th>
                <th>Deadline</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($projects as $project)
                <tr>
                    <td>{{ $project->name }}</td>
                    <td>{{ $project->client?->company_name ?? '—' }}</td>
                    <td>{{ $project->projectManager?->full_name ?? '—' }}</td>
                    <td>
                        <span class="badge badge-{{ strtolower(str_replace(' ', '', $project->status)) }}">{{ $project->status }}</span>
                    </td>
                    <td>{{ $project->progress_percent }}%</td>
                    <td>{{ $currency }} {{ number_format($project->budget ?? 0, 2) }}</td>
                    <td>{{ $currency }} {{ number_format($project->contract_value ?? 0, 2) }}</td>
                    <td>{{ $project->end_date?->format('d M Y') ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
