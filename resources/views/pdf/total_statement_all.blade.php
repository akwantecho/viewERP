<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Total Statement — All Projects</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #222; }
        h2 { margin: 0 0 8px; text-align:center; }
        .muted { color:#666; text-align:center; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 6px; text-align: center; }
        thead th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h2>Total Statement — All Projects</h2>
    <div class="muted">Generated at {{ $generated_at ?? now()->format('Y-m-d H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Project Code</th>
                <th>Project Name</th>
                <th>Units</th>
                <th>Total Price (OMR)</th>
                <th>Total Paid (OMR)</th>
                <th>Remaining (OMR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r['project_code'] }}</td>
                    <td style="text-align:left">{{ $r['project_name'] }}</td>
                    <td>{{ $r['units_count'] }}</td>
                    <td>{{ number_format($r['total_price'] ?? 0, 2) }}</td>
                    <td>{{ number_format($r['total_paid'] ?? 0, 2) }}</td>
                    <td>{{ number_format($r['remaining'] ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        @isset($totals)
        <tfoot>
            <tr>
                <th colspan="3" style="text-align:right">Totals</th>
                <th>{{ number_format($totals['total_price'] ?? 0, 2) }}</th>
                <th>{{ number_format($totals['total_paid'] ?? 0, 2) }}</th>
                <th>{{ number_format($totals['remaining'] ?? 0, 2) }}</th>
            </tr>
        </tfoot>
        @endisset
    </table>
</body>
</html>
