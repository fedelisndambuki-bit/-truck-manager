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

    @forelse ($vacancies as $vacancy)
        <h2>{{ $vacancy->title }} ({{ $vacancy->status }}) — {{ $vacancy->openings_count }} opening(s)</h2>
        <table>
            <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Background Check</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($vacancy->applications as $application)
                    <tr>
                        <td>{{ $application->candidate_name }}</td>
                        <td>{{ $application->email ?? '—' }}</td>
                        <td>{{ $application->phone ?? '—' }}</td>
                        <td>{{ $application->status }}</td>
                        <td>{{ $application->background_check_status ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No applications yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    @empty
        <p>No job vacancies found.</p>
    @endforelse
</body>
</html>
