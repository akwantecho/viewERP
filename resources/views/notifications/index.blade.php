@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-6 py-8 space-y-6">

    {{-- Header & Filters --}}
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ __('installments.notifications.title') }}</h1>
                <p class="text-sm text-gray-500">{{ __('installments.notifications.subtitle') }}</p>
            </div>
            <form method="GET" class="flex flex-wrap items-end gap-3">
                <label class="text-sm text-gray-600">
                    <span class="mb-1 block text-xs uppercase tracking-[0.2em] text-gray-500">{{ __('installments.notifications.filters.year') }}</span>
                    <input type="number" name="year" value="{{ $year ?? now()->year }}" class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0">
                </label>
                <label class="text-sm text-gray-600">
                    <span class="mb-1 block text-xs uppercase tracking-[0.2em] text-gray-500">{{ __('installments.notifications.filters.month') }}</span>
                    <input type="number" name="month" value="{{ $month ?? now()->month }}" min="1" max="12" class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:ring-0">
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="today_only" value="1" {{ !empty($todayOnly) ? 'checked' : '' }} class="rounded border-gray-300 text-gray-900 focus:ring-gray-400">
                    <span>{{ __('installments.notifications.filters.today_only') }}</span>
                </label>
                <button class="rounded-full bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800 transition">{{ __('installments.notifications.filters.submit') }}</button>
            </form>
        </div>
    </section>

    {{-- Notifications table --}}
    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-gray-700">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-[0.15em] text-gray-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('installments.notifications.table.number') }}</th>
                        <th class="px-4 py-3">{{ __('installments.notifications.table.notify_date') }}</th>
                        <th class="px-4 py-3">{{ __('installments.notifications.table.due_date') }}</th>
                        <th class="px-4 py-3">{{ __('installments.notifications.table.project') }}</th>
                        <th class="px-4 py-3">{{ __('installments.notifications.table.unit') }}</th>
                        <th class="px-4 py-3">{{ __('installments.notifications.table.customer') }}</th>
                        <th class="px-4 py-3">{{ __('installments.notifications.table.total') }}</th>
                        <th class="px-4 py-3">{{ __('installments.notifications.table.paid') }}</th>
                        <th class="px-4 py-3">{{ __('installments.notifications.table.remaining') }}</th>
                        <th class="px-4 py-3">{{ __('installments.notifications.table.status') }}</th>
                        <th class="px-4 py-3">{{ __('installments.notifications.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($installments as $idx => $inst)
                        @php
                            $amount = round($inst->amount ?? 0, 2);
                            $vat    = round($inst->vat ?? 0, 2);
                            $total  = $amount + $vat;
                            $paid   = round($inst->paid ?? 0, 2);
                            $remaining = max(0, round($total - $paid, 2));

                            $statusClasses = match($inst->status) {
                                'paid'    => 'bg-gray-100 text-gray-700',
                                'partial' => 'bg-amber-100 text-amber-700',
                                default   => 'bg-red-100 text-red-700',
                            };

                            $project = $inst->booking?->unit?->floor?->project?->name ?? '-';
                            $unit    = $inst->booking?->unit?->unit_code ?? '-';
                            $cust    = $inst->booking?->customer?->name ?? '-';

                            $notifyDate = optional($inst->notify_date)->format('Y-m-d');
                            $dueDate    = \Carbon\Carbon::parse($inst->due_date)->format('Y-m-d');
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $idx + 1 }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $notifyDate }}</td>
                            <td class="px-4 py-3 text-gray-800">
                                <div>{{ $dueDate }}</div>
                                @php
                                    $rawDays = $inst->days_to_due ?? 0;
                                    $roundedDays = (int) round($rawDays);
                                    $absDays = abs($roundedDays);
                                @endphp
                                <div class="text-xs text-gray-500">
                                    @if($roundedDays >= 0)
                                        {{ __('installments.notifications.due_in', ['days' => $roundedDays]) }}
                                    @else
                                        {{ __('installments.notifications.due_overdue', ['days' => $absDays]) }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $project }}</td>
                            <td class="px-4 py-3">{{ $unit }}</td>
                            <td class="px-4 py-3">{{ $cust }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ number_format($total, 2) }}</td>
                            <td class="px-4 py-3">{{ number_format($paid, 2) }}</td>
                            <td class="px-4 py-3">{{ number_format($remaining, 2) }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $statusKey = in_array($inst->status, ['paid','partial','unpaid'], true) ? $inst->status : 'unpaid';
                                @endphp
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">{{ __('installments.index.status.' . $statusKey) }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('installments.pay', $inst->id) }}" class="rounded-full bg-gray-900 px-3 py-1 text-xs font-semibold text-white hover:bg-gray-800 transition">{{ __('installments.index.actions.pay') }}</a>
                                    @if($inst->booking)
                                        <a href="{{ route('installments.index', $inst->booking->id) }}" class="rounded-full border border-gray-300 px-3 py-1 text-xs font-semibold text-gray-700 hover:border-gray-400 transition">{{ __('installments.index.actions.view') }}</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12 text-center text-sm text-gray-500">{{ __('installments.notifications.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <p class="text-xs text-gray-500">
        {!! __('installments.notifications.footnote_html') !!}
    </p>

</div>
@endsection
