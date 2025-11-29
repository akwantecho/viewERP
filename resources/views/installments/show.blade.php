@extends('layouts.app')

@section('content')
@php
    $resolvedBase = (float) ($installment->booking->agreed_base ?? $installment->booking->unit_price ?? 0);
    $resolvedVat = (float) ($installment->booking->agreed_vat ?? $installment->booking->vat ?? 0);
    $resolvedTotal = (float) ($installment->booking->agreed_price ?? $installment->booking->total_price ?? ($resolvedBase + $resolvedVat));
@endphp

<div class="max-w-5xl mx-auto bg-white p-6 rounded shadow space-y-6">

    {{-- Page Title --}}
    <div class="border-b pb-4 mb-4">
        <h2 class="text-2xl font-bold text-gray-800">Installment Payment Details</h2>
    </div>

    {{-- Unit & Customer Information --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-700">
        <div class="space-y-1">
            <h3 class="font-semibold text-gray-900 mb-2">Unit Information</h3>
            <p><strong>Unit Code:</strong> {{ $installment->booking->unit->unit_code ?? '-' }}</p>
            <p><strong>Project:</strong> {{ $installment->booking->unit->floor->project->name ?? '-' }}</p>
            <p><strong>Floor:</strong> {{ $installment->booking->unit->floor->name ?? '-' }}</p>
            <p><strong>Unit Price (incl. VAT):</strong> OMR {{ number_format($resolvedTotal, 2) }}</p>
        </div>
        <div class="space-y-1">
            <h3 class="font-semibold text-gray-900 mb-2">Customer Information</h3>
            <p><strong>Name:</strong> {{ $installment->booking->customer->name ?? '-' }}</p>
            <p><strong>ID Number:</strong> {{ $installment->booking->customer->id_number ?? '-' }}</p>
            <p><strong>Phone:</strong> {{ $installment->booking->customer->phone ?? '-' }}</p>
        </div>
    </div>
    

    {{-- Installment Details --}}
    <div class="mt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Installment Information</h3>
        <table class="w-full text-sm border border-gray-200 rounded overflow-hidden">
            <tbody>
                <tr class="bg-gray-50">
                    <td class="p-2 font-medium w-1/3">Payment Date</td>
                    <td class="p-2">{{ $installment->date }}</td>
                </tr>
                <tr>
                    <td class="p-2 font-medium">Installment Amount</td>
                    <td class="p-2">OMR {{ number_format($installment->amount, 2) }}</td>
                </tr>
                <tr class="bg-gray-50">
                    <td class="p-2 font-medium">VAT (5%)</td>
                    <td class="p-2">OMR {{ number_format($installment->vat, 2) }}</td>
                </tr>
                <tr>
                    <td class="p-2 font-medium">Total Due</td>
                    <td class="p-2">OMR {{ number_format($installment->total_due, 2) }}</td>
                </tr>
                <tr class="bg-gray-50">
                    <td class="p-2 font-medium">Remaining Before</td>
                    <td class="p-2">OMR {{ number_format($installment->remaining_before, 2) }}</td>
                </tr>
                <tr>
                    <td class="p-2 font-medium">Remaining After</td>
                    <td class="p-2">OMR {{ number_format($installment->remaining_after, 2) }}</td>
                </tr>
                @php $latestPayment = optional($installment->payments)->sortByDesc('paid_at')->first(); @endphp
                <tr class="bg-gray-50">
                    <td class="p-2 font-medium">Payment Method</td>
                    <td class="p-2">{{ $latestPayment->payment_method ?? $installment->payment_method ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="p-2 font-medium">Bank Name</td>
                    <td class="p-2">{{ $latestPayment->bank_name ?? $installment->bank_name ?? '—' }}</td>
                </tr>
                <tr class="bg-gray-50">
                    <td class="p-2 font-medium">Invoice No.</td>
                    <td class="p-2">{{ $latestPayment->invoice_number ?? '—' }}</td>
                </tr>
                <tr>
                    <td class="p-2 font-medium">Reference Code</td>
                    <td class="p-2">{{ $latestPayment->reference_no ?? $installment->reference_no ?? '—' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Receipt Attachment --}}
    @if($installment->receipt)
    <div class="mt-6 space-y-2">
        <h3 class="text-lg font-semibold text-gray-800">Receipt / Transfer Image</h3>
        <a href="{{ Storage::url($installment->receipt) }}" target="_blank" class="text-blue-600 underline">
            View Attached Receipt
        </a>
    </div>
    @endif

    {{-- Print Report Button --}}
   <div class="mt-6 text-right">
    <a href="{{ route('installments.print', $installment->id) }}" target="_blank"
       class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded shadow">
        🖨️ Print PDF Report
    </a>
</div>


</div>
@endsection
