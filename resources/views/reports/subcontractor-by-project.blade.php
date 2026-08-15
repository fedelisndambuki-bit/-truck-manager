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
        h2 { color: #2f4f4f; border-left: 4px solid #2f4f4f; padding-left: 10px; margin-top: 20px; margin-bottom: 12px; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #2f4f4f; color: white; text-align: left; padding: 8px; text-transform: uppercase; font-size: 10px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #fcfcfc; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — Generated on {{ $date }}</p>
    </div>

    @forelse ($projects as $project)
        <h2>{{ $project->name }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Subcontractor</th>
                    <th>Contract #</th>
                    <th>Contract Value</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($project->contracts as $contract)
                    <tr>
                        <td>{{ $contract->subcontractor?->company_name ?? '—' }}</td>
                        <td>{{ $contract->contract_number }}</td>
                        <td>{{ $currency }} {{ number_format($contract->contract_value, 2) }}</td>
                        <td>{{ $contract->status }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p>No projects with subcontractor contracts found.</p>
    @endforelse
</body>
</html>
