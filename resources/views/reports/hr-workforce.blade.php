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
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Construction</h1>
        <p>{{ $title }} — Generated on {{ $date }}</p>
    </div>
    <table>
        <thead><tr><th>Staff No.</th><th>Name</th><th>Department</th><th>Position</th><th>Employment Type</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($staff as $s)
                <tr>
                    <td>{{ $s->staff_number }}</td>
                    <td>{{ $s->full_name }}</td>
                    <td>{{ $s->department?->name ?? '—' }}</td>
                    <td>{{ $s->position?->title ?? '—' }}</td>
                    <td>{{ $s->employment_type }}</td>
                    <td>{{ $s->status }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No employees found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
