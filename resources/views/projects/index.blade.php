@extends('layouts.app')

@section('content')
<div class="w-full px-6 py-10 space-y-10">
    @php
        $totalProjects = $projects->count();
        $totalUnits = $projects->sum('units_count');
        $totalReserved = $projects->sum('reserved_units_count');
        $totalAvailable = $projects->sum('available_units_count');
        $soldPercentage = $totalUnits > 0 ? round(($totalReserved / max(1, $totalReserved + $totalAvailable)) * 100, 1) : 0;
    @endphp

    <section class="relative overflow-hidden rounded-3xl border border-[#1f2937]/10 bg-gradient-to-r from-[#1f2937]/95 via-[#374151] to-[#6b7280] text-white shadow-xl">
        <div class="absolute top-0 left-0 h-full w-full bg-[url('data:image/svg+xml,%3Csvg width%3D%27144%27 height%3D%27144%27 viewBox%3D%270 0 144 144%27 fill%3D%27none%27 xmlns%3D%27http://www.w3.org/2000/svg%27%3E%3Cpath d%3D%27M0 128H16V144H0V128Z%27 fill%3D%27rgba(255,255,255,0.06)%27/%3E%3C/svg%3E')] opacity-20"></div>
        <div class="relative z-10 flex flex-col gap-6 p-8 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.4em] text-white/70">{{ __('projects.index.hero.badge') }}</p>
                <h1 class="mt-3 text-3xl font-extrabold">{{ __('projects.index.hero.title') }}</h1>
                <p class="mt-3 text-sm text-white/80 max-w-xl">{{ __('projects.index.hero.description') }}</p>
            </div>
            <div class="flex flex-wrap gap-4 text-sm">
                <div class="rounded-2xl bg-white/15 px-5 py-4 text-left backdrop-blur-sm">
                    <p class="text-xs uppercase tracking-[0.25em] text-white/70">{{ __('projects.index.hero.stats.projects') }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ $totalProjects }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 px-5 py-4 text-left backdrop-blur-sm">
                    <p class="text-xs uppercase tracking-[0.25em] text-white/70">{{ __('projects.index.hero.stats.units') }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ $totalUnits }}</p>
                </div>
                <div class="rounded-2xl bg-white/15 px-5 py-4 text-left backdrop-blur-sm">
                    <p class="text-xs uppercase tracking-[0.25em] text-white/70">{{ __('projects.index.hero.stats.sold') }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ $soldPercentage }}%</p>
                </div>
            </div>
        </div>
    </section>

    <section class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-[#1f2937]">{{ __('projects.index.list.title') }}</h2>
            <p class="mt-1 text-sm text-gray-600">{{ __('projects.index.list.subtitle') }}</p>
        </div>
        @if(auth()->user()?->is_super)
            <a href="{{ route('projects.create') }}"
               class="inline-flex items-center gap-2 rounded-full bg-[#6b7280] px-5 py-2 text-sm font-semibold text-white shadow hover:bg-[#4b5563] transition">
                <span>➕</span>
                <span>{{ __('projects.index.list.cta') }}</span>
            </a>
        @endif
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 border-b border-gray-100 px-6 py-5 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-3 text-sm text-gray-600">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#1f2937]/10 text-[#1f2937]">📊</span>
                <span>{{ __('projects.index.list.sorted_hint') }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[960px] table-fixed text-left text-sm text-gray-700">
                <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-6 py-3">{{ __('projects.index.table.project') }}</th>
                        <th class="px-6 py-3">{{ __('projects.index.table.code') }}</th>
                        <th class="px-6 py-3 text-center">{{ __('projects.index.table.floors') }}</th>
                        <th class="px-6 py-3 text-center">{{ __('projects.index.table.units') }}</th>
                        <th class="px-6 py-3">{{ __('projects.index.table.sales_progress') }}</th>
                        <th class="px-6 py-3 text-center">{{ __('projects.index.table.sold') }}</th>
                        <th class="px-6 py-3 text-center">{{ __('projects.index.table.available') }}</th>
                        <th class="px-6 py-3 text-right">{{ __('projects.index.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($projects as $project)
                        @php
                            $projectUnits = $project->units_count;
                            $projectReserved = $project->reserved_units_count;
                            $projectAvailable = $project->available_units_count;
                            $progress = $projectUnits > 0 ? round(($projectReserved / $projectUnits) * 100, 1) : 0;
                            $progressColor = match(true) {
                                $progress >= 75 => 'bg-[#4b5563]',
                                $progress >= 40 => 'bg-[#6b7280]',
                                default => 'bg-[#9ca3af]',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/70 transition">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-[#1f2937]">{{ $project->name }}</div>
                                <p class="text-xs text-gray-500">{{ __('projects.index.table.updated', ['time' => $project->updated_at?->diffForHumans() ?? __('common.general.recently')]) }}</p>
                            </td>
                            <td class="px-6 py-4 text-gray-500">{{ $project->code }}</td>
                            <td class="px-6 py-4 text-center">{{ $project->floors_count }}</td>
                            <td class="px-6 py-4 text-center font-medium">{{ $projectUnits }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-between text-xs font-semibold text-gray-500">
                                    <span>{{ __('projects.index.table.sold_label') }}</span>
                                    <span>{{ $progress }}%</span>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-gray-100">
                                    <div class="h-full rounded-full {{ $progressColor }}" style="width: {{ min(100, $progress) }}%"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-emerald-600 font-semibold">{{ $projectReserved }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">{{ $projectAvailable }}</span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="{{ route('projects.show', $project->id) }}" class="inline-flex items-center gap-1 rounded-full border border-transparent px-3 py-1 font-semibold text-indigo-600 hover:border-indigo-100 hover:bg-indigo-50">{{ __('projects.index.table.view') }}</a>
                                @if(auth()->user()?->is_super)
                                    <a href="{{ route('projects.edit', $project->id) }}" class="ml-2 inline-flex items-center gap-1 rounded-full border border-transparent px-3 py-1 font-semibold text-amber-600 hover:border-amber-100 hover:bg-amber-50">{{ __('projects.index.table.edit') }}</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
        <span class="inline-flex items-center gap-2"><span class="h-2 w-6 rounded-full bg-[#4b5563]"></span>{{ __('projects.index.legend.high') }}</span>
        <span class="inline-flex items-center gap-2"><span class="h-2 w-6 rounded-full bg-[#6b7280]"></span>{{ __('projects.index.legend.mid') }}</span>
        <span class="inline-flex items-center gap-2"><span class="h-2 w-6 rounded-full bg-[#9ca3af]"></span>{{ __('projects.index.legend.early') }}</span>
    </div>
</div>
@endsection
