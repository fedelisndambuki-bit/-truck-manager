<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid darkslategrey; padding-bottom: 10px; }
        .header h1 { color: darkslategrey; margin: 0; text-transform: uppercase; }
        .header p { margin: 5px 0; color: #666; }
        
        .stats-container { width: 100%; margin-bottom: 30px; }
        .stat-box { width: 23%; display: inline-block; background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px; text-align: center; border-radius: 8px; }
        .stat-value { font-size: 20px; font-weight: bold; color: darkslategrey; }
        .stat-label { font-size: 10px; color: #64748b; text-transform: uppercase; margin-top: 5px; }

        h2 { color: darkslategrey; border-left: 4px solid darkslategrey; padding-left: 10px; margin-top: 20px; margin-bottom: 15px; font-size: 16px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: darkslategrey; color: white; text-align: left; padding: 8px; text-transform: uppercase; font-size: 10px; }
        td { padding: 8px; border-bottom: 1px solid #eee; }
        tr:nth-child(even) { background: #fcfcfc; }

        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
        
        .badge { padding: 2px 6px; border-radius: 10px; font-size: 9px; font-weight: bold; }
        .badge-active { background: #dcfce7; color: #16a34a; }
        .badge-spare { background: #d1fae5; color: #0284c7; }
        .badge-discarded { background: #fee2e2; color: #dc2626; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tanaki Hub Fleet Intelligence</h1>
        <p>{{ $title }} - Generated on {{ $date }}</p>
    </div>

    <div class="stats-container">
        <div class="stat-box">
            <div class="stat-value">{{ $stats['total'] }}</div>
            <div class="stat-label">Total Tyres</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $stats['available'] }}</div>
            <div class="stat-label">Available</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $stats['installed'] }}</div>
            <div class="stat-label">Installed</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">{{ $stats['retread'] }}</div>
            <div class="stat-label">Retreads</div>
        </div>
    </div>

    <h2>Tyres Installed Per Truck</h2>
    <table>
        <thead>
            <tr>
                <th>Plate Number</th>
                <th>Model</th>
                <th>Total Slots</th>
                <th>Installed</th>
                <th>Empty</th>
                <th>Fill Rate</th>
            </tr>
        </thead>
        <tbody>
            @foreach($truckReport as $row)
            <tr>
                <td><strong>{{ $row['plate_number'] }}</strong></td>
                <td>{{ $row['model'] }}</td>
                <td>{{ $row['total_slots'] }}</td>
                <td style="color: #16a34a;">{{ $row['installed'] }}</td>
                <td>{{ $row['empty'] }}</td>
                <td>{{ round($row['fill_rate']) }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Detailed Inventory Overview</h2>
    <table>
        <thead>
            <tr>
                <th>Serial Number</th>
                <th>Brand</th>
                <th>Size</th>
                <th>Tread</th>
                <th>Status</th>
                <th>Truck</th>
                <th>Position</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tyres as $tyre)
            <tr>
                <td><code>{{ $tyre->serial_number }}</code></td>
                <td>{{ $tyre->brand }}</td>
                <td>{{ $tyre->size }}</td>
                <td>{{ $tyre->tread_depth }}mm</td>
                <td>
                    <span class="badge {{ $tyre->status == 'Active' ? 'badge-active' : ($tyre->status == 'Spare' ? 'badge-spare' : 'badge-discarded') }}">
                        {{ $tyre->status }}
                    </span>
                </td>
                <td>{{ $tyre->truck->plate_number ?? '—' }}</td>
                <td>{{ $tyre->position ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        © {{ date('Y') }} Tanaki Hub - Smart Fleet Management System. Confident logistics through data.
    </div>
</body>
</html>
