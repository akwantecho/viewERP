<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Installments & Receipts</title>
    <style>
        @page { margin: 0cm 0cm; }
        body {
            margin: 3cm 2cm 2cm;
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }
        .letterhead-bg {
            position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; z-index: -1;
        }
        h1, h2, h3 { margin: 0; }
        /* Move the title 10px further down */
        h2 { text-align: center; margin: 70px 0 18px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #999; padding: 6px; text-align: center; }
        /* Only keep the date column on one line and widen it */
        .payments-table th:nth-child(2), .payments-table td:nth-child(2) { white-space: nowrap; width: 16%; }
        .meta td { border: 0; padding: 3px 0; }
        .small { font-size: 11px; color: #555; }
        .page-break { page-break-before: always; }
        /* Add extra top spacing for tables on pages after a break */
        .page-break + .payments-table { margin-top: 70px; }
        .receipt-image { width: 100%; height: auto; }
        .section { margin-top: 18px; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
    </style>
</head>
<body>

    {{-- Page background --}}
    @php
        $bgPaths = [
            public_path('images/TAXINOVS02.png'),
            public_path('images/letterhead.png'),
            public_path('images/TAXINOVS.png'),
        ];

        $bg = collect($bgPaths)->first(function ($path) {
            return file_exists($path);
        });
    @endphp
    @if($bg)
        <img src="{{ $bg }}" class="letterhead-bg">
    @endif

    <h2>All Payments - Receipt Summary</h2>

    {{-- Client / project / unit metadata --}}
    <table class="meta mb-3">
        <tr>
            <td style="text-align:left;">
                <strong>Customer Name:</strong> {{ $booking->customer->name ?? '-' }}
            </td>
            <td style="text-align:right;">
                <strong>Project:</strong> {{ $booking->unit->floor->project->name ?? '-' }}
            </td>
        </tr>
        <tr>
            <td style="text-align:left;">
                <strong>Unit Code:</strong> {{ $booking->unit->unit_code ?? '-' }}
            </td>
            <td style="text-align:right;">
                @php
                    $unitBase = (float) ($booking->agreed_base ?? $booking->unit_price ?? 0);
                    $unitVat = (float) ($booking->agreed_vat ?? $booking->vat ?? 0);
                    $unitTotal = (float) ($booking->agreed_price ?? $booking->total_price ?? ($unitBase + $unitVat));
                @endphp
                <strong>Property value (incl. VAT):</strong> {{ number_format($unitTotal, 2) }} OMR
            </td>
        </tr>
     
    </table>

    {{-- Payments listing (chronological, one row per payment) --}}
    @php
        $paymentsCollection = collect($payments ?? []);
        $rowsPerPage = 10;
        $chunks      = $paymentsCollection->chunk($rowsPerPage)->values();
    @endphp

    @if($paymentsCollection->isEmpty())
        <table class="mb-3 payments-table">
            <thead>
                <tr>
                    <th>Installment No.</th>
                    <th>Date</th>
                    <th>Bank / Method</th>
                    <th>Invoice No.</th>
                    <th>Reference Code</th>
                    <th>Amount (incl. VAT)</th>
                    <th>VAT</th>
                    <th>Previous Remaining</th>
                    <th>Remaining After Payment</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="9" style="text-align:center; padding:18px;">No payments recorded.</td>
                </tr>
            </tbody>
        </table>
    @else
        @foreach($chunks as $chunkIndex => $chunk)
            @if($chunkIndex > 0)
                <div class="page-break"></div>
            @endif
            <table class="mb-3 payments-table">
                <thead>
                    <tr>
                        <th>Installment No.</th>
                        <th>Date</th>
                        <th>Bank / Method</th>
                        <th>Invoice No.</th>
                        <th>Reference Code</th>
                        <th>Amount (incl. VAT)</th>
                        <th>VAT</th>
                        <th>Previous Remaining</th>
                        <th>Remaining After Payment</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($chunk as $row)
                        @php
                            /** @var \App\Models\Payment $paymentRow */
                            $paymentRow = $row['payment'];
                            $label = $paymentRow->installment?->installment_number ? ('#'.$paymentRow->installment->installment_number) : 'Advance';
                            $date  = optional($paymentRow->paid_at)->format('Y-m-d') ?: '-';
                            $method = trim(($paymentRow->bank_name ?? '').' / '.($paymentRow->payment_method ?? ''), ' /');
                            $invoiceNumber = $paymentRow->invoice_number ?? ('INV-'.str_pad((string) $paymentRow->id, 4, '0', STR_PAD_LEFT));
                            $refNumber = $paymentRow->reference_no ?? '—';
                            $amountIncl = (float) $paymentRow->amount;
                            $vatAmount  = $amountIncl > 0 ? round($amountIncl * 0.05, 2) : 0.00;
                            $before = $row['previous_remaining'];
                            $after  = $row['remaining_after'];
                        @endphp
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ $date }}</td>
                            <td>{{ $method !== '' ? $method : '-' }}</td>
                            <td>{{ $invoiceNumber }}</td>
                            <td>{{ $refNumber }}</td>
                            <td>{{ number_format($amountIncl, 2) }}</td>
                            <td>{{ number_format($vatAmount, 2) }}</td>
                            <td>{{ number_format($before, 2) }}</td>
                            <td>{{ number_format($after, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif

    {{-- Attachments are not rendered in this summary --}}

</body>
</html>
