<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Installment Report</title>
    <style>
        @page { margin: 0cm 0cm; }
        body {
            margin: 3cm 2cm 2cm;
            font-family: 'Arial', sans-serif;
        }
        .letterhead-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #999;
            padding: 6px;
            text-align: center;
        }
        .page-break {
            page-break-before: always;
        }
        .receipt-image {
            width: 100%;
            max-height: 800px;
            object-fit: contain;
        }
    </style>
</head>
@php
    $invoiceBase = (float) ($booking->agreed_base ?? $booking->unit_price ?? 0);
    $invoiceVat = (float) ($booking->agreed_vat ?? $booking->vat ?? 0);
    $invoiceTotal = (float) ($booking->agreed_price ?? $booking->total_price ?? ($invoiceBase + $invoiceVat));
@endphp

<body>

    {{-- الخلفية --}}
    <img src="{{ public_path('images/TAXINOVS.png') }}" class="letterhead-bg">

    {{-- العنوان --}}
    <h2 style="text-align: center; margin-bottom: 40px; margin-top: 40px;">TAX INVOICE - PROFORMA</h2>

    {{-- بيانات العميل والوحدة --}}
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td><strong>Customer Name:</strong> {{ $booking->customer->name }}</td>
            <td style="text-align: right;"><strong>Project:</strong> {{ $booking->unit->floor->project->name }}</td>
        </tr>
        <tr>
            <td><strong>Unit Code:</strong> {{ $booking->unit->unit_code }}</td>
            <td style="text-align: right;">
                <strong>Total Price (incl. VAT):</strong> {{ number_format($invoiceTotal, 2) }} OMR
            </td>
        </tr>
    </table>

    {{-- جدول الأقساط --}}
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Payment Method / Bank</th>
                <th>Transfer No. / Cheque No.</th>
                <th>Installment Amount</th>
                <th>VAT (5%)</th>
                <th>Remaining Before</th>
                <th>Remaining After</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($booking->installments->where('status', 'paid') as $i => $inst)
                @php
                    // Compute display VAT as 5% of total amount
                    $total     = round($inst->total_amount ?? (($inst->amount ?? 0) + ($inst->vat ?? 0)), 2);
                    $vatDisp   = round($total * 0.05, 2);
                    $amountDisp= round($total - $vatDisp, 2);
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($inst->paid_at)->format('d M Y') }}</td>
                    <td>{{ $inst->payment_method ?? '-' }} / {{ $inst->bank_name ?? '-' }}</td>
                    <td>{{ $inst->reference_no ?? '-' }}</td>
                    <td>{{ number_format($amountDisp, 2) }}</td>
                    <td>{{ number_format($vatDisp, 2) }}</td>
                    <td>{{ number_format($inst->remaining_before, 2) }}</td>
                    <td>{{ number_format($inst->remaining_after, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- عرض الإيصالات في صفحات مستقلة --}}
    @foreach ($booking->installments as $inst)
        @if ($inst->receipt)
            <div class="page-break"></div>
            <h3>Receipt for Installment #{{ $inst->installment_number }}</h3>
            <img src="{{ public_path('storage/' . $inst->receipt) }}" class="receipt-image" alt="Receipt Image">
        @endif
    @endforeach

</body>
</html>
