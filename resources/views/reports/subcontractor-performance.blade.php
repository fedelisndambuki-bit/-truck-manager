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
        <thead><tr><th>Subcontractor</th><th>Date</th><th>Quality</th><th>Timeliness</th><th>Safety</th><th>Communication</th><th>Cost Control</th><th>Overall</th></tr></thead>
        <tbody>
            @forelse ($reviews as $r)
                <tr>
                    <td>{{ $r->subcontractor?->company_name ?? '—' }}</td>
                    <td>{{ $r->review_date->format('d M Y') }}</td>
                    <td>{{ $r->quality_of_work_rating }}/5</td>
                    <td>{{ $r->timeliness_rating }}/5</td>
                    <td>{{ $r->safety_rating }}/5</td>
                    <td>{{ $r->communication_rating }}/5</td>
                    <td>{{ $r->cost_control_rating }}/5</td>
                    <td><strong>{{ $r->overall_rating }}/5</strong></td>
                </tr>
            @empty
                <tr><td colspan="8">No performance reviews found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
