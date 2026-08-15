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
        .total-row td { font-weight: bold; border-top: 2px solid #2f4f4f; }
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
                    <th>Material</th>
                    <th>Allocated</th>
                    <th>Consumed</th>
                    <th>Unit Cost</th>
                    <th>Consumed Cost</th>
                </tr>
            </thead>
            <tbody>
                @php $projectTotal = 0; @endphp
                @foreach ($project->materialAllocations as $allocation)
                    @php $cost = $allocation->consumed_quantity * ($allocation->material?->unit_cost ?? 0); $projectTotal += $cost; @endphp
                    <tr>
                        <td>{{ $allocation->material?->name ?? '—' }}</td>
                        <td>{{ $allocation->allocated_quantity }}</td>
                        <td>{{ $allocation->consumed_quantity }}</td>
                        <td>{{ $currency }} {{ number_format($allocation->material?->unit_cost ?? 0, 2) }}</td>
                        <td>{{ $currency }} {{ number_format($cost, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4">Total Material Cost</td>
                    <td>{{ $currency }} {{ number_format($projectTotal, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <p>No project material allocations found.</p>
    @endforelse
</body>
</html>
