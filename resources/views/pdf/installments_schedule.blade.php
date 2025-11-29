<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Payment Schedule</title>
    <style>
        @page { margin: 0cm 0cm; }
        body {
            margin: 3cm 2cm 2cm;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #000;
        }
        .letterhead-bg {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; z-index: -1;
        }
        h1, h2, h3 { margin: 0; }
        h2 { text-align: center; margin: 36px 0 18px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #999; padding: 6px; text-align: center; }
        .meta td { border: 0; padding: 3px 0; }
        .small { font-size: 11px; color: #555; }
        .page-break { page-break-before: always; break-before: page; }
        .receipt-image { max-width: 100%; height: auto; border: 1px solid #ccc; display: block; }
        .center-page { display: flex; align-items: center; justify-content: center; min-height: calc(100vh - 5cm); text-align: center; }
        .section { margin-top: 18px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
    </style>
    </head>
@php
    $scheduleBase = (float) ($booking->agreed_base ?? $booking->unit_price ?? 0);
    $scheduleVat = (float) ($booking->agreed_vat ?? $booking->vat ?? 0);
    $scheduleTotal = (float) ($booking->agreed_price ?? $booking->total_price ?? ($scheduleBase + $scheduleVat));
@endphp

<body>
      <img src="{{ public_path('images/Paid.png') }}" class="letterhead-bg" alt="">

    <h2 style="text-align:center; margin-bottom:10px;">Payment Schedule</h2>
    <table class="meta" style="margin-bottom:10px;">
        <tr>
            <td><strong>Project:</strong> {{ $booking->unit->floor->project->name ?? '-' }}</td>
            <td style="text-align:right"><strong>Unit Code:</strong> {{ $booking->unit->unit_code ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Customer:</strong> {{ $booking->customer->name ?? '-' }}</td>
            <td style="text-align:right"><strong>Unit price (incl. VAT):</strong> {{ number_format($scheduleTotal, 2) }} OMR</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Installment No.</th>
                <th>Installment Amount (OMR)</th>
                <th>Due Date</th>
            </tr>
        </thead>
        <tbody>
            @php
                // Aggregate advance payments (not linked to any installment)
                $advancePays = $booking->payments?->whereNull('installment_id') ?? collect();
                $advanceSum  = (float) $advancePays->sum('amount');
                $advanceLast = $advancePays->sortByDesc('paid_at')->first();
                $advanceDate = optional(optional($advanceLast)->paid_at)->format('Y-m-d')
                                ?: (optional($booking->reservation_date) ? \Carbon\Carbon::parse($booking->reservation_date)->format('Y-m-d') : '-');
            @endphp
            @if($advanceSum > 0)
                <tr>
                    <td>Advance</td>
                    <td>{{ number_format($advanceSum, 2) }}</td>
                    <td>{{ $advanceDate }}</td>
                </tr>
            @endif
            @foreach(($booking->installments ?? collect()) as $inst)
                @php
                    $isLast = $loop->last;
                    $amount = number_format((float)($inst->total_amount ?? ($inst->amount + $inst->vat)), 2);
                    $dateText = $isLast
                        ? 'Upon building completion certificate'
                        : (optional($inst->due_date) ? \Carbon\Carbon::parse($inst->due_date)->format('Y-m-d') : '-');
                @endphp
                <tr>
                    <td>{{ $inst->installment_number }}</td>
                    <td>{{ $amount }}</td>
                    <td>{{ $dateText }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
