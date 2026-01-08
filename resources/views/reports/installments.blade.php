@extends('layouts.app')

@section('content')
<div class="w-full px-6 py-10 space-y-10">
    {{-- Header Section --}}
    <section class="relative overflow-hidden rounded-3xl border border-[#1f2937]/10 bg-gradient-to-r from-[#1f2937]/95 via-[#374151] to-[#6b7280] text-white shadow-xl">
        <div class="absolute top-0 left-0 h-full w-full bg-[url('data:image/svg+xml,%3Csvg width%3D%27144%27 height%3D%27144%27 viewBox%3D%270 0 144 144%27 fill%3D%27none%27 xmlns%3D%27http://www.w3.org/2000/svg%27%3E%3Cpath d%3D%27M0 128H16V144H0V128Z%27 fill%3D%27rgba(255,255,255,0.06)%27/%3E%3C/svg%3E')] opacity-20"></div>
        <div class="relative z-10 p-8 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.4em] text-white/70">{{ __('installments_report.hero.badge') }}</p>
                <h1 class="mt-3 text-3xl font-extrabold">{{ __('installments_report.hero.title') }}</h1>
                <p class="mt-3 text-sm text-white/80 max-w-2xl">{{ __('installments_report.hero.description') }}</p>
            </div>
            <a href="{{ route('reports.totalStatement') }}" class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-sm font-semibold hover:bg-white/25 transition">
                ← {{ __('installments_report.back_to_statement') }}
            </a>
        </div>
    </section>

    {{-- Summary Cards --}}
    <section class="grid gap-6 md:grid-cols-3">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-full bg-emerald-100 flex items-center justify-center text-2xl">✅</div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('installments_report.summary.paid') }}</p>
                    <p class="text-2xl font-bold text-emerald-600">{{ number_format($totalPaid, 2) }} {{ __('common.currency') }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-full bg-amber-100 flex items-center justify-center text-2xl">⏳</div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('installments_report.summary.expected') }}</p>
                    <p class="text-2xl font-bold text-amber-600">{{ number_format($totalExpected, 2) }} {{ __('common.currency') }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center text-2xl">💎</div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('installments_report.summary.total') }}</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($grandTotal, 2) }} {{ __('common.currency') }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Filters --}}
    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
        <form method="GET" action="{{ route('reports.installments') }}" class="flex flex-wrap gap-4 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('installments_report.filters.month') }}</label>
                <input
                    type="month"
                    name="month"
                    value="{{ $selectedMonth }}"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                >
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('installments_report.filters.status') }}</label>
                <select
                    name="status"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                >
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>{{ __('installments_report.filters.all') }}</option>
                    <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>{{ __('installments_report.filters.paid') }}</option>
                    <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>{{ __('installments_report.filters.unpaid') }}</option>
                </select>
            </div>

            <button type="submit" class="rounded-lg bg-[#1f2937] px-6 py-2 text-sm font-semibold text-white hover:bg-[#374151] transition">
                {{ __('installments_report.filters.apply') }}
            </button>
        </form>
    </section>

    {{-- Installments Table --}}
    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            @php
                $installmentTotal = $installments->sum('total_amount');
            @endphp
            <table class="w-full text-sm text-left">
                <thead class="bg-[#1f2937] text-white text-xs uppercase">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">{{ __('installments_report.table.due_date') }}</th>
                        <th class="px-4 py-3">{{ __('installments_report.table.project') }}</th>
                        <th class="px-4 py-3">{{ __('installments_report.table.unit') }}</th>
                        <th class="px-4 py-3">{{ __('installments_report.table.customer') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('installments_report.table.installment_amount') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('installments_report.table.paid') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('installments_report.table.remaining') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('installments_report.table.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($installments as $index => $installment)
                        @php
                            $remaining = max(0, $installment->total_amount - $installment->paid_amount);
                            $statusClass = match($installment->status) {
                                'paid' => 'bg-emerald-100 text-emerald-700',
                                'partial' => 'bg-amber-100 text-amber-700',
                                'unpaid' => 'bg-red-100 text-red-700',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">{{ $installment->due_date->format('Y-m-d') }}</td>
                            <td class="px-4 py-3">{{ $installment->booking->unit->floor->project->name ?? '-' }}</td>
                            <td class="px-4 py-3 font-medium">{{ $installment->booking->unit->unit_code ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $installment->booking->customer->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-right font-semibold">{{ number_format($installment->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-600 font-semibold">{{ number_format($installment->paid_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right text-red-600 font-semibold">{{ number_format($remaining, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ __(('installments_report.status.' . $installment->status)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                {{ __('installments_report.table.no_installments') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 text-gray-900 font-semibold">
                    <tr>
                        <td colspan="5" class="px-4 py-3 text-right">{{ __('installments_report.table.installment_total') }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format($installmentTotal, 2) }} {{ __('common.currency') }}</td>
                        <td class="px-4 py-3 text-right text-gray-400">—</td>
                        <td class="px-4 py-3 text-right text-gray-400">—</td>
                        <td class="px-4 py-3"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>
</div>
@endsection
