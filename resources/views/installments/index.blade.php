@extends('layouts.app')

@section('content')
<div class="w-full px-6 py-10 space-y-10">

    {{-- Header --}}
    <div class="border-b pb-4 mb-6">
        <h2 class="text-2xl font-bold text-[#1f2937]">
            {{ __('installments.index.title', ['code' => $unit->unit_code ?? '-' ]) }}
        </h2>
        <p class="text-sm text-gray-600">
            <strong>{{ __('installments.index.project_label') }}</strong> {{ $unit->floor?->project?->name ?? '-' }} |
            <strong>{{ __('installments.index.customer_label') }}</strong> {{ $booking->customer?->name ?? '-' }}
        </p>
    </div>

    {{-- Top actions --}}
    <div class="flex gap-3 items-center flex-wrap">
        <a href="{{ route('units.show', $unit->id) }}"
           class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2 rounded shadow">
            🏠 {{ __('units.show.actions.details') }}
        </a>
        <a href="{{ route('bookings.installments.plan', $booking->id) }}"
           class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded shadow">
            {{ __('installments.index.buttons.plan_builder') }}
        </a>
        <a href="{{ route('installments.schedule', $booking->id) }}" target="_blank"
           class="bg-green-600 hover:bg-green-700 text-white text-sm px-4 py-2 rounded shadow">
            {{ __('installments.index.buttons.schedule') }}
        </a>
        <a href="{{ route('payments.index', $booking->id) }}"
           class="bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2 rounded shadow">
            {{ __('installments.index.buttons.payments') }}
        </a>
    </div>

    {{-- Unit details summary --}}
    @php
        $summaryBase = round($booking->agreed_base ?? $booking->unit_price ?? 0, 2);
        $summaryVat = round($booking->agreed_vat ?? $booking->vat ?? 0, 2);
        $summaryTotal = round($booking->agreed_price ?? $booking->total_price ?? ($summaryBase + $summaryVat), 2);
    @endphp
    <div class="mt-4 text-sm text-gray-700">
        <strong>{{ __('installments.index.summary.unit') }}</strong> {{ $unit->unit_code ?? '-' }} ·
        <strong>{{ __('installments.index.summary.floor') }}</strong> {{ $unit->floor?->name ?? '-' }} ·
        <strong>{{ __('installments.index.summary.project') }}</strong> {{ $unit->floor?->project?->code ?? '-' }} ·
        <strong>{{ __('installments.index.summary.total') }}</strong>
        OMR {{ number_format($summaryTotal, 2) }}
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto mt-6 rounded-xl shadow border border-gray-200">
        @php
            // ✅ بيانات أساسية من الـ booking
            $totalPrice      = $summaryTotal;
            $advance         = round($booking->advance_payment ?? 0, 2);
            // عرض فقط: احتساب الضريبة كـ 5% من العربون
            $advanceVat      = $advance > 0 ? round($advance * 0.05, 2) : 0.00;
            $advanceBase     = $advance > 0 ? round($advance - $advanceVat, 2) : 0.00; // excl. VAT (للجدول)

            // ✅ العربون والمدفوعات تُقرأ من payments (دفعات بدون installment_id)
            $advancePayments = $booking->payments->whereNull('installment_id');
            $advancePaid     = round($advancePayments->sum('amount'), 2);

            // مسار إيصال العربون (إن وُجد آخر إيصال)
            $advanceLatest   = $advancePayments->sortByDesc('paid_at')->first();
            $advanceReceipt  = optional($advanceLatest)->receipt;
            $advancePaidAt   = optional($advanceLatest)->paid_at;
            $advanceUrl      = null;
            if (!empty($advanceReceipt)) {
                $advanceUrl = \Illuminate\Support\Str::startsWith($advanceReceipt, ['http://','https://'])
                    ? $advanceReceipt
                    : \Illuminate\Support\Facades\Storage::url($advanceReceipt);
            }

            // لتعبئة أعمدة Before/After على نفس منطقك الحالي
            $plannedRunning  = $totalPrice;
            $beforePlanned   = $plannedRunning;
            $afterPlanned    = max(0, round($beforePlanned - $advance, 2));
            $plannedRunning  = $afterPlanned;
        @endphp

        <table class="w-full min-w-[1500px] text-[13px] text-center">
            <thead class="bg-gray-100 text-gray-700 uppercase text-xs font-semibold">
                <tr>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.number') }}</th>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.date') }}</th>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.method') }}</th>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.bank') }}</th>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.reference') }}</th>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.amount') }}</th>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.tax') }}</th>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.total') }}</th>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.paid') }}</th>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.remaining') }}</th>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.before') }}</th>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.after') }}</th>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.receipt') }}</th>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.status') }}</th>
                    <th class="border px-3 py-2">{{ __('installments.index.table_headers.actions') }}</th>
                </tr>
            </thead>
            <tbody>

                {{-- صف العربون (من booking->payments بدون installment_id) --}}
                <tr class="bg-yellow-50 font-semibold">
                    <td class="border px-3 py-2">0</td>
                    <td class="border px-3 py-2">
                        {{ $advancePaidAt ? \Carbon\Carbon::parse($advancePaidAt)->format('Y-m-d') : ($booking->reservation_date ? \Carbon\Carbon::parse($booking->reservation_date)->format('Y-m-d') : '-') }}
                    </td>
                    <td class="border px-3 py-2">
                        {{ optional($advancePayments->sortByDesc('paid_at')->first())->payment_method ? : '—' }}
                    </td>
                    <td class="border px-3 py-2">
                        {{ optional($advancePayments->sortByDesc('paid_at')->first())->bank_name ? : '—' }}
                    </td>
                    <td class="border px-3 py-2 font-mono">
                        {{ optional($advancePayments->sortByDesc('paid_at')->first())->reference_no ? : '—' }}
                    </td>

                    <td class="border px-3 py-2">{{ number_format($advanceBase, 2) }}</td>
                    <td class="border px-3 py-2">{{ number_format($advanceVat, 2) }}</td>
                    <td class="border px-3 py-2 font-semibold">{{ number_format($advance, 2) }}</td>

                    {{-- Paid/Remaining لصف العربون بناءً على الدفعات الفعلية --}}
                    <td class="border px-3 py-2">{{ number_format($advancePaid, 2) }}</td>
                    <td class="border px-3 py-2">{{ number_format(max(0, $totalPrice - $advancePaid), 2) }}</td>

                    <td class="border px-3 py-2">{{ number_format($beforePlanned, 2) }}</td>
                    <td class="border px-3 py-2">{{ number_format($afterPlanned, 2) }}</td>

                    <td class="border px-3 py-2">
                        @if($advanceUrl)
                            <a href="{{ $advanceUrl }}" target="_blank" class="text-blue-600 hover:underline">{{ __('installments.index.actions.receipt_view') }}</a>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>

                    <td class="border px-3 py-2">
                        @php
                            $advanceStatusKey = $advancePaid <= 0 ? 'unpaid' : (($advancePaid + 0.0001) >= $advance ? 'paid' : 'partial');
                            $advanceStatus = __('installments.index.status.' . $advanceStatusKey);
                            $advanceColor  = $advanceStatusKey === 'paid' ? 'bg-green-100 text-green-700'
                                            : ($advanceStatusKey === 'partial' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700');
                        @endphp
                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $advanceColor }}">{{ $advanceStatus }}</span>
                    </td>

                    <td class="border px-3 py-2">—</td>
                </tr>

                {{-- باقي الأقساط --}}
                @foreach ($installments as $i => $inst)
                    @php
                        // اجمالي القسط (عادة مخزّن داخل total_amount)
                        $baseFromDb = round($inst->amount ?? 0, 2);        // القيمة المحاسبية بدون ضريبة
                        // Fallback total from base using new rule: base = total - total*0.05 => total = base / 0.95
                        $total      = round($inst->total_amount ?? ($baseFromDb > 0 ? round($baseFromDb / 0.95, 2) : 0), 2);
                        // عرض فقط: 5% من الإجمالي
                        $vat        = round($total * 0.05, 2);
                        // عرض فقط: المبلغ بدون ضريبة = الإجمالي - الضريبة (مثال 700 → 665)
                        $amount     = round($total - $vat, 2);

                        // المدفوعات من العلاقة (بدلاً من مصفوفات خارجية)
                        $instPayments = $inst->payments ?? collect();
                        $paid         = round($instPayments->sum('amount'), 2);
                        $remainThis   = max(0, round($total - $paid, 2));

                        // before/after على نمطك الحالي
                        $beforePlanned = round($plannedRunning, 2);
                        $afterPlanned  = max(0, round($beforePlanned - $total, 2));
                        $plannedRunning = $afterPlanned;

                        // آخر ميتا للعرض (method/bank/ref/receipt)
                        $lastPay = $instPayments->sortByDesc('paid_at')->first();
                        $receiptUrl = null;
                        if (!empty(optional($lastPay)->receipt)) {
                            $receiptUrl = \Illuminate\Support\Str::startsWith($lastPay->receipt, ['http://','https://'])
                                ? $lastPay->receipt
                                : \Illuminate\Support\Facades\Storage::url($lastPay->receipt);
                        }

                        // الحالة
                        $statusKey = $paid <= 0 ? 'unpaid' : (($paid + 0.0001) >= $total ? 'paid' : 'partial');
                        $status = __('installments.index.status.' . $statusKey);
                        $statusColor = $statusKey === 'paid'
                            ? 'bg-green-100 text-green-700'
                            : ($statusKey === 'partial'
                                ? 'bg-yellow-100 text-yellow-700'
                                : 'bg-red-100 text-red-700');
                    @endphp

                    <tr class="hover:bg-gray-50">
                        <td class="border px-3 py-2 font-semibold">{{ $inst->installment_number }}</td>
                        <td class="border px-3 py-2">
                            {{ optional(optional($lastPay)->paid_at)->format('Y-m-d') ?: ($inst->due_date ? \Carbon\Carbon::parse($inst->due_date)->format('Y-m-d') : '-') }}
                        </td>
                    <td class="border px-3 py-2">{{ optional($lastPay)->payment_method ?? '—' }}</td>
                    <td class="border px-3 py-2">{{ optional($lastPay)->bank_name ?? '—' }}</td>
                    <td class="border px-3 py-2 font-mono">{{ optional($lastPay)->reference_no ?? '—' }}</td>

                        <td class="border px-3 py-2">{{ number_format($amount, 2) }}</td>
                        <td class="border px-3 py-2">{{ number_format($vat, 2) }}</td>
                        <td class="border px-3 py-2 font-semibold">{{ number_format($total, 2) }}</td>
                        <td class="border px-3 py-2">{{ number_format($paid, 2) }}</td>
                        <td class="border px-3 py-2">{{ number_format($remainThis, 2) }}</td>

                        <td class="border px-3 py-2">{{ number_format($beforePlanned, 2) }}</td>
                        <td class="border px-3 py-2">{{ number_format($afterPlanned, 2) }}</td>

                        <td class="border px-3 py-2">
                            @if($receiptUrl)
                                <a href="{{ $receiptUrl }}" target="_blank"
                                   class="inline-flex items-center gap-1 text-blue-600 hover:underline">{{ __('installments.index.actions.receipt_view') }}</a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="border px-3 py-2">
                            <span class="px-2 py-1 rounded text-xs font-semibold {{ $statusColor }}">{{ $status }}</span>
                        </td>

                        <td class="border px-3 py-2 space-y-1">
                            @if($statusKey !== 'paid')
                                <a href="{{ route('installments.pay', $inst->id) }}"
                                   class="block bg-blue-100 text-blue-800 hover:bg-blue-200 text-xs px-2 py-1 rounded">
                                    {{ __('installments.index.actions.pay') }}
                                </a>
                            @endif

                            @if($lastPay)
                                <a href="{{ route('payments.show', ['booking' => $booking->id, 'payment' => $lastPay->id]) }}"
                                   class="block bg-gray-100 text-gray-800 hover:bg-gray-200 text-xs px-2 py-1 rounded">
                                    {{ __('installments.index.actions.view') }}
                                </a>
                            @else
                                <span class="block text-xs text-gray-400 px-2 py-1">{{ __('installments.index.actions.none') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
@endsection
