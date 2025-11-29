@extends('layouts.app')

@section('hide_back', true)

@section('content')
<div class="px-6 py-10 space-y-10">
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#1f2937] via-[#374151] to-[#6b7280] text-white shadow-xl">
        <div class="absolute -top-24 -right-16 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-20 -left-10 w-56 h-56 bg-white/5 rounded-full blur-2xl"></div>
        <div class="absolute -top-8 left-12 h-20 w-20 rounded-full bg-[#fbbf24]/40 blur-2xl"></div>
        <div class="absolute bottom-6 right-12 h-16 w-16 rounded-full bg-[#f59e0b]/30 blur-xl"></div>
        <div class="relative z-10 grid gap-6 md:grid-cols-2">
            <div class="p-8">
                <div class="inline-flex items-center gap-2 rounded-full bg-[#fbbf24]/90 px-4 py-1 text-xs font-semibold text-[#1f2937] shadow">
                    <span>✨</span>
                    <span>{{ __('dashboard.hero.badge') }}</span>
                </div>
                <p class="mt-4 text-sm uppercase tracking-[0.4em] text-white/70">{{ __('dashboard.hero.welcome') }}</p>
                <h1 class="mt-3 text-3xl font-extrabold">{{ __('dashboard.hero.title') }}</h1>
                <p class="mt-4 text-sm text-white/80 leading-6">{{ __('dashboard.hero.description') }}</p>
                <div class="mt-6 flex flex-wrap gap-3 text-sm">
                    <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 font-semibold hover:bg-white/25 transition">
                        <span>📁</span><span>{{ __('dashboard.hero.actions.projects') }}</span>
                    </a>
                    <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 font-semibold hover:bg-white/25 transition">
                        <span>👥</span><span>{{ __('dashboard.hero.actions.customers') }}</span>
                    </a>
                </div>
            </div>
            <div class="p-8 grid grid-cols-2 gap-4 md:border-l md:border-white/20">
                <div class="rounded-2xl border border-[#fbbf24]/60 bg-white/15 p-5 shadow-lg backdrop-blur-sm">
                    <p class="text-xs uppercase tracking-[0.2em] text-white/70">{{ __('dashboard.cards.projects.label') }}</p>
                    <p class="mt-3 text-3xl font-extrabold">{{ $projectsCount }}</p>
                    <p class="mt-2 text-xs text-white/70">{{ __('dashboard.cards.projects.hint') }}</p>
                </div>
                <div class="rounded-2xl border border-[#fbbf24]/60 bg-white/15 p-5 shadow-lg backdrop-blur-sm">
                    <p class="text-xs uppercase tracking-[0.2em] text-white/70">{{ __('dashboard.cards.customers.label') }}</p>
                    <p class="mt-3 text-3xl font-extrabold">{{ $customersCount }}</p>
                    <p class="mt-2 text-xs text-white/70">{{ __('dashboard.cards.customers.hint') }}</p>
                </div>
                <div class="rounded-2xl border border-white/20 bg-white/15 p-5 shadow-lg backdrop-blur-sm">
                    <p class="text-xs uppercase tracking-[0.2em] text-white/70">{{ __('dashboard.cards.units_sold.label') }}</p>
                    <p class="mt-3 text-3xl font-extrabold">{{ $soldUnits }}</p>
                    <p class="mt-2 text-xs text-white/70">{{ __('dashboard.cards.units_sold.hint') }}</p>
                </div>
                <div class="rounded-2xl border border-white/20 bg-white/15 p-5 shadow-lg backdrop-blur-sm">
                    <p class="text-xs uppercase tracking-[0.2em] text-white/70">{{ __('dashboard.cards.available_units.label') }}</p>
                    <p class="mt-3 text-3xl font-extrabold">{{ $availableUnits }}</p>
                    <p class="mt-2 text-xs text-white/70">{{ __('dashboard.cards.available_units.hint') }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-[#1f2937]">{{ __('dashboard.quick_actions.title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">{{ __('dashboard.quick_actions.subtitle') }}</p>
                </div>
                <div class="grid gap-4 px-6 py-5 md:grid-cols-2">
                    <a href="{{ route('projects.create') }}" class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/60 px-4 py-4 text-sm text-gray-700 hover:border-[#6b7280]/40 hover:bg-white transition">
                        <span class="mt-1 inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#fbbf24]/80 text-[#1f2937]">{{ __('dashboard.quick_actions.items.new_project.icon') }}</span>
                        <span>
                            <strong class="block text-[#1f2937]">{{ __('dashboard.quick_actions.items.new_project.title') }}</strong>
                            {{ __('dashboard.quick_actions.items.new_project.description') }}
                        </span>
                    </a>
                    <a href="{{ route('customers.create') }}" class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/60 px-4 py-4 text-sm text-gray-700 hover:border-[#6b7280]/40 hover:bg-white transition">
                        <span class="mt-1 inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#fde68a] text-[#1f2937]">{{ __('dashboard.quick_actions.items.register_customer.icon') }}</span>
                        <span>
                            <strong class="block text-[#1f2937]">{{ __('dashboard.quick_actions.items.register_customer.title') }}</strong>
                            {{ __('dashboard.quick_actions.items.register_customer.description') }}
                        </span>
                    </a>
                    <a href="{{ route('projects.index') }}" class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/60 px-4 py-4 text-sm text-gray-700 hover:border-[#6b7280]/40 hover:bg-white transition">
                        <span class="mt-1 inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#fbbf24]/80 text-[#1f2937]">{{ __('dashboard.quick_actions.items.project_pipeline.icon') }}</span>
                        <span>
                            <strong class="block text-[#1f2937]">{{ __('dashboard.quick_actions.items.project_pipeline.title') }}</strong>
                            {{ __('dashboard.quick_actions.items.project_pipeline.description') }}
                        </span>
                    </a>
                    <a href="{{ route('installments.notifications') }}" class="flex items-start gap-3 rounded-xl border border-gray-100 bg-gray-50/60 px-4 py-4 text-sm text-gray-700 hover:border-[#6b7280]/40 hover:bg-white transition">
                        <span class="mt-1 inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#fde68a] text-[#1f2937]">{{ __('dashboard.quick_actions.items.upcoming_installments.icon') }}</span>
                        <span>
                            <strong class="block text-[#1f2937]">{{ __('dashboard.quick_actions.items.upcoming_installments.title') }}</strong>
                            {{ __('dashboard.quick_actions.items.upcoming_installments.description') }}
                        </span>
                    </a>
                </div>
            </div>
        </div>
        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-[#1f2937]">{{ __('dashboard.sales_health.title') }}</h2>
                </div>
                <div class="px-6 py-5 space-y-4 text-sm text-gray-700">
                    <div>
                        <div class="flex items-center justify-between">
                            <span>{{ __('dashboard.sales_health.units_sold') }}</span>
                            <span class="font-semibold text-[#1f2937]">{{ $soldUnits }}</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-gray-100">
                            @php
                                $totalTrackedUnits = max(1, $soldUnits + $availableUnits);
                                $soldRatio = round(($soldUnits / $totalTrackedUnits) * 100, 1);
                            @endphp
                            <div class="flex h-full items-center rounded-full bg-[#1f2937]/20">
                                <div class="h-full rounded-full bg-gradient-to-r from-[#fbbf24] via-[#f59e0b] to-[#6b7280]" style="width: {{ $soldRatio }}%"></div>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ __('dashboard.sales_health.progress_hint', ['value' => $soldRatio]) }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 px-4 py-3">
                        <p class="text-xs uppercase tracking-[0.3em] text-gray-500">{{ __('dashboard.sales_health.tip_label') }}</p>
                        <p class="mt-2 text-sm text-gray-600">{{ __('dashboard.sales_health.tip_body') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
