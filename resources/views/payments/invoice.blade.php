@extends('layouts.app')

@push('styles')
<style>
    @media print {
        body { margin: 0; background: #fff !important; }
        .no-print { display: none !important; }
        body * { visibility: hidden; }
        #invoice-print-wrapper, #invoice-print-wrapper * { visibility: visible; }
        #invoice-print-wrapper {
            position: absolute;
            inset: 0;
            margin: 0 !important;
            padding: 0 !important;
        }
        #invoice-print-wrapper .print-sheet {
            box-shadow: none !important;
            border-radius: 0 !important;
            border: none !important;
            width: 100% !important;
            height: auto !important;
        }
    }
</style>
@endpush

@section('content')
@php
    $customer = $booking->customer;
    $unit = $booking->unit;
    $project = optional($unit->floor)->project;

    $invoiceNo = $payment->invoice_number ?? ('INV-' . str_pad($payment->id, 4, '0', STR_PAD_LEFT));
    $invoiceDate = optional($payment->paid_at)->format('Y-m-d') ?? now()->format('Y-m-d');
    $reference = $payment->reference_no ?? '—';
    $installmentLabel = $payment->installment ? 'Installment #' . $payment->installment->installment_number : 'Advance';

    $amountInclVat = (float) $payment->amount;
    $vatAmount = $amountInclVat > 0 ? round($amountInclVat * 0.05, 2) : 0.00;

    $bankMethod = trim(($payment->bank_name ?? '') . ' / ' . ($payment->payment_method ?? ''), ' /') ?: '—';

    $taxCard = config('company.tax_card') ?? '27528897';
    $vatin = config('company.vatin') ?? 'OM1100457466';
    $footerLine = config('company.address_line') ?? 'MUSCAT HILLS - MUSCAT - SULTANATE OF OMAN · 968 9323 0103 · 968 9756 6600 · CR:1583304 · POST CODE:320 · P.O.BOX:547 · INFO@VIEVOM.COM';
@endphp

<div class="max-w-6xl mx-auto px-4 py-8 space-y-5">
    <div class="no-print flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('payments.index', $booking->id) }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to payments</a>
            <button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-600 hover:bg-slate-100" @click="sidebarMini = !sidebarMini">
                Toggle sidebar
            </button>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('payments.print', [$booking->id, $payment->id]) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-50">🖨️ Download PDF</a>
            <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">⬆️ Browser Print</button>
        </div>
    </div>

    <div id="invoice-print-wrapper" class="flex justify-center">
        <div class="print-sheet relative w-full max-w-[794px] h-[1123px] rounded-2xl border border-white/70 bg-white shadow-2xl shadow-slate-900/20 overflow-hidden">
            <img src="{{ asset('images/page1.png') }}" alt="Background" class="absolute inset-0 h-full w-full object-cover opacity-95">
            <div class="relative z-10 flex h-full flex-col gap-6 px-6 sm:px-10 lg:px-14 pt-16 pb-14 text-slate-800">
               

              

                <div class="mt-40 flex flex-col gap-3 text-sm md:flex-row md:items-start md:justify-between">
                    <div class="space-y-1">
                        <p><span class="font-semibold">Customer:</span> {{ $customer->name ?? '—' }}</p>
                        <p><span class="font-semibold">Project:</span> {{ $project->name ?? '—' }}</p>
                    </div>
                    <div class="space-y-1 text-right">
                        <p><span class="font-semibold">Unit Code:</span> {{ $unit->unit_code ?? '—' }}</p>
                        <p><span class="font-semibold">Date:</span> {{ $invoiceDate }}</p>
                        <p><span class="font-semibold">Reference:</span> {{ $reference }}</p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border border-slate-200/70 bg-white/90">
                    <table class="w-full border-collapse text-[10px] text-slate-800">
                        <thead class="bg-slate-100 text-[9px] font-semibold uppercase tracking-[0.25em] text-slate-600">
                            <tr>
                                <th class="px-2 py-2 text-left">Installment</th>
                                <th class="px-2 py-2">Date</th>
                                <th class="px-2 py-2">Bank / Method</th>
                                <th class="px-2 py-2">Invoice No</th>
                                <th class="px-2 py-2">Reference Code</th>
                                <th class="px-2 py-2">Amount (incl. VAT)</th>
                                <th class="px-2 py-2">VAT</th>
                                <th class="px-2 py-2">Previous Remaining</th>
                                <th class="px-2 py-2">Remaining After Payment</th>
                            </tr>
                        </thead>
                        <tbody class="text-center text-[10px]">
                            <tr class="divide-x divide-slate-200 border-t border-slate-200">
                                <td class="px-2 py-3 text-left font-semibold">{{ $installmentLabel }}</td>
                                <td class="px-2 py-3">{{ $invoiceDate }}</td>
                                <td class="px-2 py-3">{{ $bankMethod }}</td>
                                <td class="px-2 py-3">{{ $invoiceNo }}</td>
                                <td class="px-2 py-3">{{ $reference }}</td>
                                <td class="px-2 py-3">{{ number_format($amountInclVat, 2) }}</td>
                                <td class="px-2 py-3">{{ number_format($vatAmount, 2) }}</td>
                                <td class="px-2 py-3">{{ number_format($previousRemaining, 2) }}</td>
                                <td class="px-2 py-3">{{ number_format($remainingAfter, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

               

              
            </div>
        </div>
    </div>
</div>
@endsection
