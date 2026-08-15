<!DOCTYPE html>
<html lang="en">
<head>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .section { margin-top: 20px; }
        .label { font-weight: bold; width: 150px; display: inline-block; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2 f2 f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>TRIP MANIFEST</h1>
        <p>Trip ID: #{{ $trip->id }} | Date: {{ $trip->departure_time->format('d M Y') }}</p>
    </div>

    <div class="section">
        <h3>1. Assignment Details</h3>
        <p><span class="label">Driver:</span> {{ $trip->driver->full_name }}</p>
        <p><span class="label">Truck:</span> {{ $trip->truck->plate_number }} ({{ $trip->truck->truck_type }})</p>
    </div>

    <div class="section">
        <h3>2. Route Information</h3>
        <p><span class="label">Route:</span> {{ $trip->route_name }}</p>
        <p><span class="label">Origin:</span> {{ $trip->origin }}</p>
        <p><span class="label">Destination:</span> {{ $trip->destination }}</p>
    </div>

    <div class="section">
        <h3>3. Cargo & Client</h3>
        <table>
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Cargo Description</th>
                    <th>Type</th>
                    <th>Weight</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $trip->client->company_name }}</td>
                    <td>{{ $trip->cargo->description }}</td>
                    <td>{{ $trip->cargo_type }}</td>
                    <td>{{ $trip->cargo_weight }} Tons</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section" style="margin-top: 50px;">
        <p>__________________________<br>Driver Signature</p>
    </div>
</body>
</html>