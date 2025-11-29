<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Installment Receipt</title>
    <style>
        @page { margin: 0; }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #1f2937;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .page {
            position: relative;
            width: 210mm;
            height: 297mm;
            overflow: hidden;
        }

        .page + .page {
            page-break-before: always;
        }

        .content {
            padding: 32mm 24mm 24mm;
            height: 100%;
            box-sizing: border-box;
        }

        h1, h2, h3 {
            margin: 0;
        }

        h2 {
            font-size: 20px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        h3 {
            font-size: 14px;
            margin-bottom: 6px;
            color: #0f172a;
        }

        .muted { color: #6b7280; font-size: 11px; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }

        th {
            background: #f3f4f6;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .summary {
            width: 48%;
        }

        .summary td { border: none; padding: 4px 0; }

        .summary td:first-child {
            font-weight: 600;
            color: #4b5563;
            width: 40%;
        }

        .grid {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .card {
            flex: 1 1 220px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 14px;
        }

        .section {
            margin-top: 20px;
        }

        .amount-table td, .amount-table th {
            text-align: right;
        }

        .amount-table td:first-child,
        .amount-table th:first-child {
            text-align: left;
        }

        .footer {
            position: absolute;
            left: 24mm;
            right: 24mm;
            bottom: 14mm;
            font-size: 11px;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pagenum::before { content: "Page " counter(page) " of " counter(pages); }

        .receipt-wrapper {
            border: 1px dashed #d1d5db;
            border-radius: 12px;
            padding: 12px;
            height: calc(100% - 80px);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .receipt-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            background: #fef3c7;
            color: #92400e;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .mt-2 { margin-top: 8px; }
        .mt-3 { margin-top: 12px; }
        .mb-1 { margin-bottom: 4px; }
    </style>
</head>
@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    $customer = $booking->customer;
    $unit = $booking->unit;
    $project = optional($unit->floor)->project;

    $invoiceBase = (float) ($booking->agreed_base ?? $booking->unit_price ?? 0);
    $invoiceVat  = (float) ($booking->agreed_vat ?? $booking->vat ?? 0);
    $invoiceTotal= (float) ($booking->agreed_price ?? $booking->total_price ?? ($invoiceBase + $invoiceVat));

    $installmentAmount = (float) ($installment->amount ?? 0);
    $installmentVat    = (float) ($installment->vat ?? max(0, ($installment->total_amount ?? 0) - $installmentAmount));
    $installmentTotal  = (float) ($installment->total_amount ?? ($installmentAmount + $installmentVat));

    $remainingBefore = isset($installment->remaining_before)
        ? (float) $installment->remaining_before
        : max(0, $invoiceTotal - $installmentTotal);
    $remainingAfter  = isset($installment->remaining_after)
        ? (float) $installment->remaining_after
        : max(0, $remainingBefore - $installmentTotal);

    $dueDate = $installment->due_date ? Carbon::parse($installment->due_date)->format('Y-m-d') : '—';
    $paidDate = $installment->paid_at ? Carbon::parse($installment->paid_at)->format('Y-m-d') : '—';
    $statusLabel = strtoupper(str_replace('_', ' ', $installment->status ?? 'pending'));
    $installmentLabel = 'Installment #' . ($installment->installment_number ?? '—');
    $invoiceCode = 'INST-' . str_pad((string) ($installment->id ?? 0), 5, '0', STR_PAD_LEFT);

    $receiptLink = null;
    $receiptImage = null;
    if (!empty($installment->receipt)) {
        $receiptLink = Str::startsWith($installment->receipt, ['http://','https://'])
            ? $installment->receipt
            : asset('storage/' . ltrim($installment->receipt, '/'));

        $localPath = public_path('storage/' . ltrim($installment->receipt, '/'));
        if (file_exists($localPath)) {
            $receiptImage = $localPath;
        }
    }
@endphp
<body>
    <div class="page">
        <div class="content">
            <div class="mb-1">
                <div class="badge">Official Receipt</div>
                <h2>{{ $installmentLabel }}</h2>
                <p class="muted">Generated at {{ now()->format('Y-m-d H:i') }}</p>
            </div>

            <div class="grid">
                <div class="card">
                    <h3>Customer</h3>
                    <p><strong>Name:</strong> {{ $customer->name ?? '—' }}</p>
                    <p><strong>Phone:</strong> {{ $customer->phone ?? '—' }}</p>
                    <p><strong>Email:</strong> {{ $customer->email ?? '—' }}</p>
                </div>
                <div class="card">
                    <h3>Project / Unit</h3>
                    <p><strong>Project:</strong> {{ $project->name ?? '—' }}</p>
                    <p><strong>Unit Code:</strong> {{ $unit->unit_code ?? '—' }}</p>
                    <p><strong>Floor:</strong> {{ optional($unit->floor)->name ?? '—' }}</p>
                </div>
                <div class="card">
                    <h3>Billing</h3>
                    <p><strong>Total Price:</strong> {{ number_format($invoiceTotal, 2) }} OMR</p>
                    <p><strong>Due Date:</strong> {{ $dueDate }}</p>
                    <p><strong>Paid Date:</strong> {{ $paidDate }}</p>
                </div>
            </div>

            <div class="section">
                <h3>Installment Summary</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Invoice Code</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                            <th>Bank</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $invoiceCode }}</td>
                            <td>{{ $statusLabel }}</td>
                            <td>{{ $installment->payment_method ?? '—' }}</td>
                            <td>{{ $installment->bank_name ?? '—' }}</td>
                            <td>{{ $installment->reference_no ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h3>Amount Breakdown</h3>
                <table class="amount-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount (OMR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Base Amount</td>
                            <td>{{ number_format($installmentAmount, 2) }}</td>
                        </tr>
                        <tr>
                            <td>VAT (5%)</td>
                            <td>{{ number_format($installmentVat, 2) }}</td>
                        </tr>
                        <tr>
                            <td><strong>Total Paid</strong></td>
                            <td><strong>{{ number_format($installmentTotal, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <td>Remaining Before Payment</td>
                            <td>{{ number_format($remainingBefore, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Remaining After Payment</td>
                            <td>{{ number_format($remainingAfter, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h3>Notes</h3>
                <p class="muted">
                    This receipt confirms the settlement of {{ strtolower($installmentLabel) }} for the above unit.
                    Please retain this document for your records. Contact the finance team if any detail appears incorrect.
                </p>
                @if($receiptLink)
                    <p class="muted mt-2">Receipt file: {{ $receiptLink }}</p>
                @endif
            </div>
        </div>

        <div class="footer">
            <span>{{ $project->code ?? '—' }} / {{ $unit->unit_code ?? '—' }}</span>
            <span class="pagenum"></span>
        </div>
    </div>

    @if($receiptImage || $receiptLink)
        <div class="page">
            <div class="content">
                <h3>Receipt Attachment</h3>
                <div class="receipt-wrapper">
                    @if($receiptImage)
                        <img src="{{ $receiptImage }}" alt="Installment Receipt" class="receipt-image">
                    @else
                        <div class="muted text-center">
                            Receipt stored at:<br>
                            <a href="{{ $receiptLink }}" style="color:#1d4ed8; text-decoration:none;">{{ $receiptLink }}</a>
                        </div>
                    @endif
                </div>
            </div>
            <div class="footer">
                <span>Receipt Attachment</span>
                <span class="pagenum"></span>
            </div>
        </div>
    @endif
</body>
</html>
