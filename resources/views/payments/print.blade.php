<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8" />
  <title>{{ $title ?? 'Document' }}</title>

<style>
  @page { margin: 0; }

  /* Body without padding; padding moved into .content per page */
  body {
    margin: 0;
    font-family: DejaVu Sans, sans-serif;
    font-size: 12px;
    color: #000;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  /* Each PDF page as a container (A4) */
  .page {
    position: relative;
    width: 210mm;
    height: 297mm;
    overflow: hidden;
  }
  .page + .page { page-break-before: always; }

  /* Page background per page */
  .page-bg {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    z-index: -1; /* behind content */
  }

  /* Printable area (moved from body padding) */
  .content {
    padding: 3cm 2cm 2cm; /* top 3cm, sides 2cm, bottom 2cm */
  }

  h1, h2, h3 { margin: 0; }
  h2 { text-align:center; margin: 50px 0 18px; }

  table { width:100%; border-collapse:collapse; font-size:12px; }
  th, td { border:1px solid #999; padding:6px; text-align:center; }

  /* Repeat header/footer when table breaks pages + avoid row splits */
  thead { display: table-header-group; }
  tfoot { display: table-footer-group; }
  tr, td, th { page-break-inside: avoid; }

  .payments-table { table-layout:auto; }
  .payments-table th:nth-child(2),
  .payments-table td:nth-child(2) { white-space:nowrap; width:16%; }

  .meta td { border:0; padding:3px 0; }
  .small { font-size:11px; color:#555; }

  /* Attachment page layout */
  .attachment-table { width:100%; border-collapse:collapse; table-layout:fixed; }
  .attachment-table tr, .attachment-table td { height:23.7cm; }
  .attachment-table td { vertical-align:middle; text-align:center; border:0; padding:0; }
  .receipt-image { display:inline-block; max-width:100%; max-height:23cm; height:auto; }

  /* Footer per page (page numbers supported by Dompdf) */
  .footer {
    position: absolute;
    left: 2cm; right: 2cm; bottom: 1cm;
    font-size:11px; color:#444;
    display:flex; justify-content:space-between; align-items:center;
  }
  .pagenum::before { content: "Page " counter(page) " of " counter(pages); }

  .section { margin-top: 18px; }
  .mt-2 { margin-top: 8px; }
  .mt-3 { margin-top: 12px; }
  .mb-2 { margin-bottom: 8px; }
  .mb-3 { margin-bottom: 12px; }
</style>
</head>
<body>

  {{-- ======================= PAGE 1: INVOICE ======================= --}}
  <div class="page">
    <img src="{{ public_path('images/page1.png') }}" class="page-bg" alt="">

    <div class="content">
      <!-- Optional header -->
      <div class="header">
        {{-- Example: {{ $companyName ?? 'Your Company Name' }} --}}
      </div>

      <!-- Main content -->
      @php
        $invoiceNo = $payment->invoice_number ?? ('INV-' . str_pad((string) $payment->id, 4, '0', STR_PAD_LEFT));
      @endphp
      <h2 style="text-align:center; margin:70px 0 10px;">TAX INVOICE ({{ $invoiceNo }})</h2>

      {{-- Booking / Customer meta --}}
      <table style="margin-bottom:14px; border:0;">
        <tr>
          <td style="border:0; text-align:left;">
            <strong>Customer:</strong> {{ $booking->customer->name ?? '-' }}<br>
            <strong>Project:</strong> {{ $booking->unit->floor->project->name ?? '-' }}
          </td>
          <td style="border:0; text-align:right;">
            <strong>Unit Code:</strong> {{ $booking->unit->unit_code ?? '-' }}<br>
            <strong>Date:</strong> {{ optional($payment->paid_at)->format('Y-m-d') ?? now()->format('Y-m-d') }}<br>
            <strong>Reference:</strong> {{ $payment->reference_no ?? '—' }}
          </td>
        </tr>
      </table>

      {{-- Single payment row only --}}
      @php
        $date       = optional($payment->paid_at)->format('Y-m-d') ?: '-';
        $ref        = $payment->reference_no ?? '—';
        $amount     = (float) $payment->amount;
        $vat        = $amount > 0 ? round($amount * 0.05, 2) : 0.00;
        $base       = $amount > 0 ? round($amount - $vat, 2) : 0.00;
        $label      = $payment->installment ? ('#' . (int) $payment->installment->installment_number) : 'Advance';
      @endphp

      <table class="payments-table">
        <thead>
          <tr>
            <th>Installment</th>
            <th>Date</th>
            <th>Invoice No</th>
            <th>Reference Code</th>
            <th>Amount (incl. VAT)</th>
            <th>VAT</th>
            <th>Previous Remaining</th>
            <th>Remaining After Payment</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>{{ $label }}</td>
            <td>{{ $date }}</td>
            <td>{{ $invoiceNo }}</td>
            <td>{{ $ref }}</td>
            <td>{{ number_format($amount, 2) }}</td>
            <td>{{ number_format($vat, 2) }}</td>
            <td>{{ number_format($previousRemaining, 2) }}</td>
            <td>{{ number_format($remainingAfter, 2) }}</td>
          </tr>
        </tbody>
      </table>
    </div> <!-- /content -->

    <div class="footer">
      <span></span>
      <span class="pagenum"></span>
    </div>
  </div> <!-- /page 1 -->

  {{-- ======================= PAGE 2: RECEIPT (optional) ======================= --}}
  @if(!empty($payment->receipt))
    <div class="page">
      <img src="{{ public_path('images/page2.png') }}" class="page-bg" alt="">

      <div class="content">
        @php $ext = strtolower(pathinfo($payment->receipt, PATHINFO_EXTENSION)); @endphp

        @if(in_array($ext, ['jpg','jpeg','png','webp','gif']) && !empty($receiptDataUri))
          <table class="attachment-table">
            <tr>
              <td>
                <img src="{{ $receiptDataUri }}" alt="Receipt" class="receipt-image">
              </td>
            </tr>
          </table>
        @else
          <div class="small" style="text-align:center;">
            File: {{ basename($payment->receipt) }} (cannot preview in PDF)
          </div>
        @endif
      </div> <!-- /content -->

      <div class="footer">
        <span></span>
        <span class="pagenum"></span>
      </div>
    </div> <!-- /page 2 -->
  @endif

</body>
</html>
