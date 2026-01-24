<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Financial Statement - {{ $project->name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 20px;
        }
        h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        h2 {
            font-size: 14px;
            color: #4b5563;
            margin-bottom: 20px;
        }
        .meta {
            margin-bottom: 20px;
            color: #6b7280;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        tfoot td {
            background-color: #e5e7eb;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .currency {
            font-weight: 600;
        }
    </style>
</head>
<body>
    <h1>Financial Statement</h1>
    <h2>{{ $project->name }} ({{ $project->code }})</h2>
    <div class="meta">Generated on: {{ now()->format('Y-m-d H:i') }}</div>

    @php
        $totalPaid = 0;
        $totalPrice = 0;
    @endphp

    <table>
        <thead>
            <tr>
                <th>Project Code</th>
                <th>Floor</th>
                <th>Unit</th>
                <th>Paid Amount</th>
                <th>Sale Price</th>
                <th>Progress %</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($units as $unit)
                @php
                    $paid = $unit->booking ? $unit->booking->payments->sum('amount') : 0;
                    $price = $unit->booking->total_price ?? 0;
                    $progress = $price > 0 ? round(($paid / $price) * 100, 2) : 0;

                    $totalPaid += $paid;
                    $totalPrice += $price;
                @endphp
                <tr>
                    <td>{{ $unit->floor->project->code ?? '-' }}</td>
                    <td>{{ $unit->floor->name ?? 'unknown' }}</td>
                    <td>{{ $unit->unit_code }}</td>
                    <td class="currency">{{ number_format($paid, 2) }} OMR</td>
                    <td>{{ number_format($price, 2) }} OMR</td>
                    <td>{{ $progress }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right">Total</td>
                <td class="currency">{{ number_format($totalPaid, 2) }} OMR</td>
                <td>{{ number_format($totalPrice, 2) }} OMR</td>
                <td>{{ $totalPrice > 0 ? round(($totalPaid / $totalPrice) * 100, 2) : 0 }}%</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
