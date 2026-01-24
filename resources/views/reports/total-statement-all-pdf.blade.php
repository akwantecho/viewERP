<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Total Statement - All Projects</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 20px;
        }
        h1 {
            font-size: 20px;
            margin-bottom: 5px;
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
        .text-left {
            text-align: left;
        }
        .currency {
            font-weight: 600;
        }
        .progress-bar {
            background-color: #e5e7eb;
            border-radius: 4px;
            height: 8px;
            width: 60px;
            display: inline-block;
            margin-right: 5px;
        }
        .progress-fill {
            background-color: #10b981;
            height: 100%;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>Total Statement - All Projects</h1>
    <div class="meta">Generated on: {{ now()->format('Y-m-d H:i') }}</div>

    @php
        $grandTotalSale = 0;
        $grandTotalPaid = 0;
    @endphp

    <table>
        <thead>
            <tr>
                <th class="text-left">Project Name</th>
                <th>Code</th>
                <th>Total Sale Price</th>
                <th>Total Paid</th>
                <th>Remaining</th>
                <th>Progress</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($projects as $project)
                @php
                    $grandTotalSale += $project->total_sale_price;
                    $grandTotalPaid += $project->total_paid;
                @endphp
                <tr>
                    <td class="text-left">{{ $project->name }}</td>
                    <td>{{ $project->code }}</td>
                    <td class="currency">{{ number_format($project->total_sale_price, 2) }} OMR</td>
                    <td class="currency">{{ number_format($project->total_paid, 2) }} OMR</td>
                    <td>{{ number_format($project->remaining, 2) }} OMR</td>
                    <td>{{ $project->progress }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="text-right">Grand Total</td>
                <td class="currency">{{ number_format($grandTotalSale, 2) }} OMR</td>
                <td class="currency">{{ number_format($grandTotalPaid, 2) }} OMR</td>
                <td>{{ number_format($grandTotalSale - $grandTotalPaid, 2) }} OMR</td>
                <td>{{ $grandTotalSale > 0 ? round(($grandTotalPaid / $grandTotalSale) * 100, 1) : 0 }}%</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
