@extends('layouts.app')

@section('content')
@php
    $booking->loadMissing(['installments' => fn ($q) => $q->orderBy('due_date')]);
    $totalPrice = (float) ($booking->total_price ?? 0);
    $totalPaid = round($payments->sum('amount'), 2);
    $remaining = max(0, round($totalPrice - $totalPaid, 2));
@endphp

<div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 py-10 space-y-8">

    <header class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('payments.index.title') }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('payments.index.subtitle', [
                    'project' => $booking->unit->floor->project->name ?? '—',
                    'unit' => $booking->unit->unit_code ?? '—',
                    'customer' => $booking->customer->name ?? '—',
                ]) }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('installments.index', $booking->id) }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:bg-slate-100">{{ __('payments.index.buttons.installments') }}</a>
            <a href="{{ route('bookings.installments.plan', $booking->id) }}" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 px-3 py-2 text-sm text-blue-600 hover:bg-blue-50">{{ __('payments.index.buttons.plan') }}</a>
            <a href="{{ route('payments.printAll', ['booking' => $booking->id]) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-600 hover:bg-slate-100">{{ __('payments.index.buttons.print_all') }}</a>
            @can('bookings.delete')
                <form action="{{ route('bookings.destroy', $booking->id) }}" method="POST" data-confirm="delete" data-confirm-title="{{ __('payments.index.confirm_booking.title') }}" data-confirm-message="{{ __('payments.index.confirm_booking.message') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-200 px-3 py-2 text-sm text-red-600 hover:bg-red-50">{{ __('payments.index.buttons.delete_booking') }}</button>
                </form>
            @endcan
        </div>
    </header>

    @if(session('success'))
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('payments.index.alerts.total') }}</p>
            <p class="mt-1 text-xl font-semibold text-slate-900">OMR {{ number_format($totalPrice, 2) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('payments.index.alerts.paid') }}</p>
            <p class="mt-1 text-xl font-semibold text-emerald-600">OMR {{ number_format($totalPaid, 2) }}</p>
        </div>
        <div class="rounded-lg border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('payments.index.alerts.remaining') }}</p>
            <p class="mt-1 text-xl font-semibold text-amber-600">OMR {{ number_format($remaining, 2) }}</p>
        </div>
    </section>

    <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
        <table class="w-full min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-3">#</th>
                    <th class="px-5 py-3">{{ __('payments.index.table.headers.index') }}</th>
                    <th class="px-5 py-3">{{ __('payments.index.table.headers.invoice') }}</th>
                    <th class="px-5 py-3">{{ __('payments.index.table.headers.type') }}</th>
                    <th class="px-5 py-3">{{ __('payments.index.table.headers.amount') }}</th>
                    <th class="px-5 py-3">{{ __('payments.index.table.headers.method') }}</th>
                    <th class="px-5 py-3">{{ __('payments.index.table.headers.reference') }}</th>
                    <th class="px-5 py-3">{{ __('payments.index.table.headers.paid_at') }}</th>
                    <th class="px-5 py-3">{{ __('payments.index.table.headers.receipt') }}</th>
                    <th class="px-5 py-3 text-right">{{ __('payments.index.table.headers.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($payments as $i => $p)
                    @php
                        $receiptUrl = null;
                        if (!empty($p->receipt)) {
                            $receiptUrl = \Illuminate\Support\Str::startsWith($p->receipt, ['http://','https://'])
                                ? $p->receipt
                                : \Illuminate\Support\Facades\Storage::url($p->receipt);
                        }
                        $typeLabel = optional($p->installment)->installment_number
                            ? __('payments.index.type_label.installment', ['number' => optional($p->installment)->installment_number])
                            : __('payments.index.type_label.advance');
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-xs text-slate-500">{{ $i + 1 }}</td>
                        <td class="px-5 py-3 font-mono text-slate-800">{{ $p->invoice_number ?? '—' }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $typeLabel }}</td>
                        <td class="px-5 py-3 font-semibold text-slate-900">{{ __('payments.index.misc.currency') }} {{ number_format($p->amount, 2) }}</td>
                        <td class="px-5 py-3 text-slate-600">
                            <div>{{ $p->payment_method ?? '—' }}</div>
                            <div class="text-xs text-slate-400">{{ $p->bank_name ?? __('payments.index.misc.no_bank') }}</div>
                        </td>
                        <td class="px-5 py-3 font-mono text-slate-600">{{ $p->reference_no ?? '—' }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ optional($p->paid_at)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td class="px-5 py-3">
                            @if($receiptUrl)
                                <a href="{{ $receiptUrl }}" target="_blank" class="text-sm text-slate-600 hover:text-slate-900">{{ __('payments.index.table.receipt_view') }}</a>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex justify-end gap-2 text-xs">
                                <a href="{{ route('payments.show', ['booking' => $booking->id, 'payment' => $p->id]) }}" class="rounded border border-slate-200 px-2 py-1 text-slate-600 hover:bg-slate-100">{{ __('payments.index.actions.view') }}</a>
                                <a href="{{ route('payments.invoice', ['booking' => $booking->id, 'payment' => $p->id]) }}" target="_blank" rel="noopener" class="rounded border border-emerald-200 px-2 py-1 text-emerald-600 hover:bg-emerald-50">{{ __('payments.index.actions.invoice') }}</a>
                                <a href="{{ route('payments.print', ['booking' => $booking->id, 'payment' => $p->id]) }}" target="_blank" rel="noopener" class="rounded border border-amber-200 px-2 py-1 text-amber-600 hover:bg-amber-50">{{ __('payments.index.actions.print') }}</a>
                                <a href="{{ route('payments.edit', ['booking' => $booking->id, 'payment' => $p->id]) }}" class="rounded border border-blue-200 px-2 py-1 text-blue-600 hover:bg-blue-50">{{ __('payments.index.actions.edit') }}</a>
                                @can('payments.delete')
                                    <form action="{{ route('payments.destroy', ['booking' => $booking->id, 'payment' => $p->id]) }}" method="POST" data-confirm="delete" data-confirm-title="{{ __('payments.index.confirm_payment.title') }}" data-confirm-message="{{ __('payments.index.confirm_payment.message') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded border border-red-200 px-2 py-1 text-red-600 hover:bg-red-50">{{ __('payments.index.actions.delete') }}</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-5 py-10 text-center text-sm text-slate-400">{{ __('payments.index.table.empty') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
