@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-6 py-10 space-y-6">
    <div class="flex items-start justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#1f2937]">Payment Details</h1>
            <p class="text-sm text-gray-600">
                <strong>Project:</strong> {{ $booking->unit->floor->project->name ?? '-' }} |
                <strong>Unit:</strong> {{ $booking->unit->unit_code ?? '-' }} |
                <strong>Customer:</strong> {{ $booking->customer->name ?? '-' }}
            </p>
        </div>
        <a href="{{ route('payments.print', ['booking' => $booking->id, 'payment' => $payment->id]) }}" target="_blank" rel="noopener"
           class="bg-yellow-500 hover:bg-yellow-600 text-white text-sm px-4 py-2 rounded shadow">
            🖨️ Open PDF
        </a>
    </div>

    @php
        $receiptUrl = null;
        if (!empty($payment->receipt)) {
            $receiptUrl = \Illuminate\Support\Str::startsWith($payment->receipt, ['http://','https://'])
                ? $payment->receipt
                : \Illuminate\Support\Facades\Storage::url($payment->receipt);
        }
        // If this payment is linked to an installment, prepare its financials
        $inst = $payment->installment;
        $haveInst = !empty($inst);
        if ($haveInst) {
            $paidSum = round(($inst->payments?->sum('amount')) ?? 0, 2);
            $total   = round(($inst->total_amount ?? (($inst->amount ?? 0) + ($inst->vat ?? 0))), 2);
            $vat     = round(($inst->vat ?? ($total * 0.05)), 2);
            $base    = round(($inst->amount ?? ($total - $vat)), 2);
            $remain  = max(0, round($total - $paidSum, 2));
        }
    @endphp

    <div class="bg-white rounded-xl shadow border overflow-hidden">
        <table class="w-full text-sm">
            <tbody>
                {{-- Payment Details --}}
                <tr class="bg-gray-50">
                    <td class="px-4 py-2 w-1/3 font-medium">Installment</td>
                    <td class="px-4 py-2">
                        {{ $haveInst ? ('#'.$inst->installment_number) : 'Advance/General' }}
                    </td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-medium">Payment Amount</td>
                    <td class="px-4 py-2">OMR {{ number_format($payment->amount, 2) }}</td>
                </tr>
                <tr class="bg-gray-50">
                    <td class="px-4 py-2 font-medium">Payment Method</td>
                    <td class="px-4 py-2">{{ $payment->payment_method ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-medium">Bank</td>
                    <td class="px-4 py-2">{{ $payment->bank_name ?? '—' }}</td>
                </tr>
                <tr class="bg-gray-50">
                    <td class="px-4 py-2 font-medium">Invoice No.</td>
                    <td class="px-4 py-2 font-mono">{{ $payment->invoice_number ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-medium">Reference Code</td>
                    <td class="px-4 py-2 font-mono">{{ $payment->reference_no ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2 font-medium">Paid At</td>
                    <td class="px-4 py-2">{{ optional($payment->paid_at)->format('Y-m-d H:i') ?? '—' }}</td>
                </tr>
                <tr class="bg-gray-50">
                    <td class="px-4 py-2 font-medium">Receipt</td>
                    <td class="px-4 py-2">
                        @if($receiptUrl)
                            <a href="{{ $receiptUrl }}" target="_blank" class="text-blue-600 hover:underline">📎 View Receipt</a>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                </tr>

                {{-- Installment Details (merged into same table) --}}
                @if($haveInst)
                    <tr>
                        <td class="px-4 py-2 font-medium">Installment Due Date</td>
                        <td class="px-4 py-2">{{ optional($inst->due_date)->format('Y-m-d') ?? '—' }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-4 py-2 font-medium">Installment Amount (excl. VAT)</td>
                        <td class="px-4 py-2">OMR {{ number_format($base, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-medium">VAT (5%)</td>
                        <td class="px-4 py-2">OMR {{ number_format($vat, 2) }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-4 py-2 font-medium">Installment Total (incl. VAT)</td>
                        <td class="px-4 py-2">OMR {{ number_format($total, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-medium">Installment Paid</td>
                        <td class="px-4 py-2">OMR {{ number_format($paidSum, 2) }}</td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td class="px-4 py-2 font-medium">Installment Remaining</td>
                        <td class="px-4 py-2">OMR {{ number_format($remain, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2 font-medium">Installment Status</td>
                        <td class="px-4 py-2">{{ ucfirst($inst->status ?? 'unpaid') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div>
        <a href="{{ route('payments.index', $booking->id) }}" class="text-sm text-gray-600 underline">Back to payments</a>
    </div>
</div>
@endsection
