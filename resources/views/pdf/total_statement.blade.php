<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Total Statement</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
    </style>
</head>
<body>
    <h2>Total Statement - {{ $project->name }} ({{ $project->code }})</h2>
    <p>Date: {{ now()->format('Y-m-d') }}</p>

    {{-- جدول مالي كمثال --}}
    <table>
        <thead>
            <tr>
                <th>Floor</th>
                <th>Unit</th>
                <th>Amount Paid</th>
                <th>Sale Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($project->units as $unit)
            <tr>
                <td>{{ $unit->floor->name }}</td>
                <td>{{ $unit->unit_code }}</td>
                <td>{{ number_format($unit->installments->sum('paid'), 2) }}</td>
                <td>{{ number_format($unit->base_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
