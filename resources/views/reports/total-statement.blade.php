@extends('layouts.app')

@section('content')
<div class="w-full px-6 py-10 space-y-10">
    {{-- Header Section --}}
    <section class="relative overflow-hidden rounded-3xl border border-[#1f2937]/10 bg-gradient-to-r from-[#1f2937]/95 via-[#374151] to-[#6b7280] text-white shadow-xl">
        <div class="absolute top-0 left-0 h-full w-full bg-[url('data:image/svg+xml,%3Csvg width%3D%27144%27 height%3D%27144%27 viewBox%3D%270 0 144 144%27 fill%3D%27none%27 xmlns%3D%27http://www.w3.org/2000/svg%27%3E%3Cpath d%3D%27M0 128H16V144H0V128Z%27 fill%3D%27rgba(255,255,255,0.06)%27/%3E%3C/svg%3E')] opacity-20"></div>
        <div class="relative z-10 p-8 flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.4em] text-white/70">{{ __('total_statement.hero.badge') }}</p>
                <h1 class="mt-3 text-3xl font-extrabold">{{ __('total_statement.hero.title') }}</h1>
                <p class="mt-3 text-sm text-white/80 max-w-2xl">{{ __('total_statement.hero.description') }}</p>
            </div>
            <a href="{{ route('reports.installments') }}" class="inline-flex items-center gap-2 rounded-full bg-white/15 px-5 py-2.5 text-sm font-semibold hover:bg-white/25 transition">
                <span>📋</span>
                <span>{{ __('total_statement.view_installments') }}</span>
            </a>
        </div>
    </section>

    {{-- Monthly Summary Cards --}}
    <section class="grid gap-6 md:grid-cols-3">
        {{-- Current Month Card --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-12 w-12 rounded-full bg-emerald-100 flex items-center justify-center text-2xl">💰</div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('total_statement.cards.current_month.label') }}</p>
                    <p class="text-xs text-gray-400">{{ now()->format('F Y') }}</p>
                </div>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-600">{{ __('total_statement.cards.paid') }}</span>
                    <span class="text-sm font-bold text-emerald-600">{{ number_format($currentMonthPaid, 2) }} {{ __('common.currency') }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-600">{{ __('total_statement.cards.expected') }}</span>
                    <span class="text-sm font-bold text-amber-600">{{ number_format($currentMonthExpected, 2) }} {{ __('common.currency') }}</span>
                </div>
                <div class="pt-2 border-t border-gray-100">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-semibold text-gray-700">{{ __('total_statement.cards.total') }}</span>
                        <span class="text-lg font-bold text-gray-900">{{ number_format($currentMonthPaid + $currentMonthExpected, 2) }} {{ __('common.currency') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Next Month Card --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="h-12 w-12 rounded-full bg-amber-100 flex items-center justify-center text-2xl">📅</div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ __('total_statement.cards.next_month.label') }}</p>
                    <p class="text-xs text-gray-400">{{ now()->addMonth()->format('F Y') }}</p>
                </div>
            </div>
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-600">{{ __('total_statement.cards.paid') }}</span>
                    <span class="text-sm font-bold text-emerald-600">{{ number_format($nextMonthPaid, 2) }} {{ __('common.currency') }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs text-gray-600">{{ __('total_statement.cards.expected') }}</span>
                    <span class="text-sm font-bold text-amber-600">{{ number_format($nextMonthExpected, 2) }} {{ __('common.currency') }}</span>
                </div>
                <div class="pt-2 border-t border-gray-100">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-semibold text-gray-700">{{ __('total_statement.cards.total') }}</span>
                        <span class="text-lg font-bold text-gray-900">{{ number_format($nextMonthPaid + $nextMonthExpected, 2) }} {{ __('common.currency') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Month Selector Card --}}
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
            <form method="GET" action="{{ route('reports.totalStatement') }}" class="space-y-3">
                <label class="text-xs uppercase tracking-wide text-gray-500">{{ __('total_statement.cards.select_month.label') }}</label>
                <input
                    type="month"
                    name="month"
                    value="{{ $selectedMonth }}"
                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                    onchange="this.form.submit()"
                >
                <div class="space-y-2 mt-3">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-600">{{ __('total_statement.cards.paid') }}</span>
                        <span class="text-sm font-bold text-emerald-600">{{ number_format($selectedMonthPaid, 2) }} {{ __('common.currency') }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-600">{{ __('total_statement.cards.expected') }}</span>
                        <span class="text-sm font-bold text-amber-600">{{ number_format($selectedMonthExpected, 2) }} {{ __('common.currency') }}</span>
                    </div>
                    <div class="pt-2 border-t border-gray-100">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-semibold text-gray-700">{{ __('total_statement.cards.total') }}</span>
                            <span class="text-lg font-bold text-blue-600">{{ number_format($selectedMonthPaid + $selectedMonthExpected, 2) }} {{ __('common.currency') }}</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

    {{-- Projects Section --}}
    <section>
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-[#1f2937]">{{ __('total_statement.projects.title') }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ __('total_statement.projects.subtitle') }}</p>
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($projects as $project)
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden hover:shadow-lg transition">
                    {{-- Project Header --}}
                    <div class="bg-gradient-to-r from-[#1f2937] to-[#374151] p-5 text-white">
                        <h3 class="text-lg font-bold">{{ $project->name }}</h3>
                        <p class="text-xs text-white/70 mt-1">{{ __('total_statement.projects.code') }}: {{ $project->code }}</p>
                    </div>

                    {{-- Project Stats --}}
                    <div class="p-5 space-y-4">
                        {{-- Sale Price --}}
                        <div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">{{ __('total_statement.projects.sale_price') }}</span>
                                <span class="text-sm font-semibold text-gray-900">{{ number_format($project->total_sale_price, 2) }} {{ __('common.currency') }}</span>
                            </div>
                        </div>

                        {{-- Paid Amount --}}
                        <div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">{{ __('total_statement.projects.paid_amount') }}</span>
                                <span class="text-sm font-semibold text-emerald-600">{{ number_format($project->total_paid, 2) }} {{ __('common.currency') }}</span>
                            </div>
                        </div>

                        {{-- Remaining --}}
                        <div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">{{ __('total_statement.projects.remaining') }}</span>
                                <span class="text-sm font-semibold text-red-600">{{ number_format($project->remaining, 2) }} {{ __('common.currency') }}</span>
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-semibold text-gray-500">{{ __('total_statement.projects.progress') }}</span>
                                <span class="text-xs font-semibold text-gray-700">{{ $project->progress }}%</span>
                            </div>
                            <div class="h-3 rounded-full bg-gray-100 overflow-hidden">
                                @php
                                    $progressColor = match(true) {
                                        $project->progress >= 75 => 'bg-emerald-500',
                                        $project->progress >= 40 => 'bg-amber-500',
                                        default => 'bg-red-400',
                                    };
                                @endphp
                                <div class="h-full rounded-full {{ $progressColor }} transition-all" style="width: {{ min(100, $project->progress) }}%"></div>
                            </div>
                        </div>

                        {{-- View Details Button --}}
                        <div class="pt-3 border-t border-gray-100">
                            <a href="{{ route('projects.statement', $project->id) }}" 
                               class="block text-center text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition">
                                {{ __('total_statement.projects.view_details') }} →
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12 text-gray-500">
                    {{ __('total_statement.projects.no_projects') }}
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
