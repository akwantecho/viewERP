@extends('layouts.app')

@section('content')
@php
    $booking = $booking ?? $unit->booking;
    $project = $unit->floor->project ?? null;
    $fallbackBase = $unit->base_price ?? 0;
    $basePrice = $booking?->agreed_base ?? $booking?->unit_price ?? $fallbackBase;
    $vat = $booking?->agreed_vat ?? $booking?->vat ?? 0;
    $totalPrice = $booking?->agreed_price ?? $booking?->total_price ?? ($basePrice + $vat);
    $statusKey = in_array($unit->status, ['sold','reserved','available']) ? $unit->status : 'default';
    $statusLabel = __('units.show.status.' . $statusKey);
    $statusStyles = match($unit->status) {
        'sold' => 'bg-red-100 text-red-700',
        'reserved' => 'bg-amber-100 text-amber-700',
        default => 'bg-emerald-100 text-emerald-700',
    };
    $projectName = $project->name ?? __('units.show.labels.not_available');
    $projectCode = $project->code ?? __('units.show.labels.unknown');
    $floorName = $unit->floor->name ?? __('units.show.labels.unknown');
    $lastUpdated = $unit->updated_at?->diffForHumans() ?? __('units.show.labels.unknown');
    $currency = __('units.show.labels.currency');
    $areaValue = $unit->area_sqm;
    $areaDisplay = $areaValue ? number_format($areaValue, 2) : null;
    $formatCurrency = function ($value) use ($currency) {
        $formatted = number_format($value, 2);
        return app()->isLocale('ar') ? $formatted . ' ' . $currency : $currency . ' ' . $formatted;
    };
@endphp

<div class="w-full px-6 py-10 space-y-10">
    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
            {{ session('success') }}
        </div>
    @endif
    @error('base_price')
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
            {{ $message }}
        </div>
    @enderror
    <section class="relative overflow-hidden rounded-3xl border border-emerald-900/10 bg-gradient-to-r from-[#0a2f1b] via-[#0f5d32] to-[#18a05f] text-white shadow-xl">
        <div class="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-gray-100/10 blur-3xl"></div>
        <div class="absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-gray-100/10 blur-3xl"></div>
        <div class="relative z-10 grid gap-6 p-8 lg:grid-cols-[2fr_1fr]">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold tracking-[0.4em] uppercase">{{ __('units.show.badge') }}</span>
                    <span class="rounded-full bg-gray-900/10 px-3 py-1 text-xs font-semibold {{ $statusStyles }}">{{ $statusLabel }}</span>
                </div>
                <h1 class="text-3xl font-extrabold text-white">{{ $unit->unit_code }}</h1>
                <div class="grid gap-2 text-sm text-white/80 sm:grid-cols-2">
                    <p><span class="font-semibold text-white">{{ __('units.show.labels.project') }}:</span> {{ $projectName }}</p>
                    <p><span class="font-semibold text-white">{{ __('units.show.labels.project_code') }}:</span> {{ $projectCode }}</p>
                    <p><span class="font-semibold text-white">{{ __('units.show.labels.floor') }}:</span> {{ $floorName }}</p>
                    <p><span class="font-semibold text-white">{{ __('units.show.labels.updated') }}:</span> {{ $lastUpdated }}</p>
                </div>
                <div class="mt-6 flex flex-wrap gap-3">
                    <div class="rounded-2xl bg-white/15 px-5 py-4 text-emerald-50 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-[0.25em] text-white/70">{{ __('units.show.metrics.total') }}</p>
                        <p class="mt-2 text-2xl font-bold text-white">{{ $formatCurrency($totalPrice) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/15 px-5 py-4 text-emerald-50 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-[0.25em] text-white/70">{{ __('units.show.metrics.base') }}</p>
                        <p class="mt-2 text-2xl font-bold text-white">{{ $formatCurrency($basePrice) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/15 px-5 py-4 text-emerald-50 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-[0.25em] text-white/70">{{ __('units.show.metrics.vat') }}</p>
                        <p class="mt-2 text-2xl font-bold text-white">{{ $formatCurrency($vat) }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/15 px-5 py-4 text-emerald-50 backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-[0.25em] text-white/70">{{ __('units.show.metrics.area') }}</p>
                        <p class="mt-2 text-2xl font-bold text-white">
                            {{ $areaDisplay ? __('units.show.metrics.area_with_unit', ['value' => $areaDisplay]) : __('units.show.labels.unknown') }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="flex flex-col justify-between gap-4">
                <div class="rounded-2xl border border-white/15 bg-gray-900/15 px-5 py-4 text-sm text-white shadow-lg backdrop-blur-sm">
                    <p class="font-semibold text-white">{{ __('units.show.summary.title') }}</p>
                    <p class="mt-2 text-white/80">{{ __('units.show.summary.description', ['project' => $projectName, 'floor' => $floorName]) }}</p>
                    <p class="mt-2 text-white/70">{{ __('units.show.summary.status', ['status' => $statusLabel]) }}</p>
                </div>
                <div class="flex flex-col gap-2">
                    @if($unit->status === 'available')
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('units.booking.form', $unit->id) }}"
                               class="flex-1 min-w-[140px] rounded-full bg-white px-5 py-2 text-center text-sm font-semibold text-[#0f5d32] shadow hover:bg-white/90 transition">{{ __('units.show.actions.reserve') }}</a>
                            <a href="{{ route('units.direct-sale.form', $unit->id) }}"
                               class="flex-1 min-w-[140px] rounded-full border border-white/30 bg-transparent px-5 py-2 text-center text-sm font-semibold text-white shadow hover:bg-white/10 transition">{{ __('units.show.actions.direct_sale') }}</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if(in_array($unit->status, ['reserved', 'sold']) && $booking && $booking->customer)
                <div class="rounded-2xl border border-emerald-900/10 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-6 py-5">
                    <h2 class="text-lg font-semibold text-[#0f5d32]">{{ __('units.show.customer.title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('units.show.customer.description') }}</p>
                    </div>
                    <div class="grid gap-4 px-6 py-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <p class="text-xs uppercase tracking-[0.3em] text-gray-500">{{ __('units.show.customer.name') }}</p>
                            <p class="mt-2 text-sm font-semibold text-[#1f2937]">{{ $booking->customer->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.3em] text-gray-500">{{ __('units.show.customer.phone') }}</p>
                            <p class="mt-2 text-sm text-gray-700">{{ $booking->customer->phone ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.3em] text-gray-500">{{ __('units.show.customer.email') }}</p>
                            <p class="mt-2 text-sm text-gray-700">{{ $booking->customer->email ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="rounded-2xl border border-emerald-900/10 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-5">
                    <h2 class="text-lg font-semibold text-[#0f5d32]">{{ __('units.show.key_actions.title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('units.show.key_actions.description') }}</p>
                </div>
                <div class="grid gap-4 px-6 py-6 md:grid-cols-3">
                    <div class="flex flex-col gap-3 rounded-xl border border-emerald-900/10 bg-emerald-50/60 p-5">
                        <div class="flex items-center justify-between">
                            <span class="text-lg">💰</span>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">{{ __('units.show.key_actions.installments.badge') }}</span>
                        </div>
                        <p class="text-sm text-gray-600">{{ __('units.show.key_actions.installments.description') }}</p>
                        <div class="mt-auto">
                            @if(!empty($booking))
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('installments.index', $booking->id) }}" class="inline-flex items-center gap-2 rounded-full bg-[#0f5d32] px-4 py-2 text-sm font-semibold text-white shadow hover:bg-[#0c4a27]">{{ __('units.show.key_actions.installments.schedule') }}</a>
                                </div>
                            @else
                                <span class="inline-flex rounded-full bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-500">{{ __('units.show.messages.no_booking') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 rounded-xl border border-emerald-900/10 bg-emerald-50/60 p-5">
                        <div class="flex items-center justify-between">
                            <span class="text-lg">💵</span>
                            <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">{{ __('units.show.key_actions.payments.badge') }}</span>
                        </div>
                        <p class="text-sm text-gray-600">{{ __('units.show.key_actions.payments.description') }}</p>
                        <div class="mt-auto">
                            @if(!empty($booking))
                                <a href="{{ route('payments.index', $booking->id) }}" class="inline-flex items-center gap-2 rounded-full bg-[#0f5d32] px-4 py-2 text-sm font-semibold text-white shadow hover:bg-[#0c4a27]">{{ __('units.show.key_actions.payments.open') }}</a>
                            @else
                                <span class="inline-flex rounded-full bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-500">{{ __('units.show.messages.no_booking') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 rounded-xl border border-emerald-900/10 bg-emerald-50/60 p-5">
                        <div class="flex items-center justify-between">
                            <span class="text-lg">📂</span>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">{{ __('units.show.key_actions.documents.badge') }}</span>
                        </div>
                        <p class="text-sm text-gray-600">{{ __('units.show.key_actions.documents.description') }}</p>
                        <div class="mt-auto">
                            <a href="{{ route('units.documents', $unit->id) }}" class="inline-flex items-center gap-2 rounded-full bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-amber-600">{{ __('units.show.key_actions.documents.open') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-emerald-900/10 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-5">
                    <h2 class="text-lg font-semibold text-[#0f5d32]">{{ __('units.show.financial.title') }}</h2>
                </div>
                <div class="px-6 py-6 space-y-4 text-sm text-gray-700">
                    <div class="flex items-center justify-between">
                        <span>{{ __('units.show.financial.booking_date') }}</span>
                        <span class="font-semibold text-gray-600">{{ $booking?->reservation_date?->format('Y-m-d') ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>{{ __('units.show.financial.total_price') }}</span>
                        <span class="font-semibold text-[#0f5d32]">{{ $formatCurrency($totalPrice) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>{{ __('units.show.financial.advance_paid') }}</span>
                        <span class="font-semibold text-emerald-600">{{ $formatCurrency($booking->advance_payment ?? 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>{{ __('units.show.financial.remaining') }}</span>
                        <span class="font-semibold text-amber-600">{{ $formatCurrency($booking->remaining_amount ?? 0) }}</span>
                    </div>
                    <div class="mt-4 rounded-xl bg-emerald-50 px-4 py-3 text-xs text-gray-500">
                        {{ __('units.show.financial.last_update', ['date' => $booking?->updated_at?->format('Y-m-d H:i') ?? __('units.show.labels.unknown')]) }}
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-emerald-900/10 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-6 py-5">
                    <h2 class="text-lg font-semibold text-[#0f5d32]">{{ __('units.show.specs.title') }}</h2>
                    <p class="text-sm text-gray-500">{{ __('units.show.specs.description') }}</p>
                </div>
                <form method="POST" action="{{ route('units.base-price.update', $unit) }}" class="px-6 py-5 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('units.show.specs.base_label') }}</label>
                        <input type="number" name="base_price" step="0.01" min="0" value="{{ old('base_price', $unit->base_price ?? 0) }}"
                               class="mt-1 w-full rounded-2xl border border-gray-200 px-4 py-2 text-sm focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200">
                        @error('base_price')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('units.show.specs.area_label') }}</label>
                        <input type="number" name="area_sqm" step="0.01" min="0" value="{{ old('area_sqm', $unit->area_sqm) }}"
                               class="mt-1 w-full rounded-2xl border border-gray-200 px-4 py-2 text-sm focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200">
                        @error('area_sqm')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-6 py-2 text-sm font-semibold text-white shadow hover:bg-emerald-700">
                            {{ __('units.show.specs.submit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
@endsection
