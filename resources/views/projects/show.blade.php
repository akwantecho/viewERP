@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-6 space-y-8">

    {{-- Project header --}}
    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-4 p-6 md:flex-row md:items-center md:justify-between">
            <div class="space-y-2">
                <span class="inline-flex items-center rounded-full bg-[#f8d147]/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.25em] text-[#8f6b00]">{{ __('projects.show.badge') }}</span>
                <h1 class="text-2xl font-bold text-gray-900">{{ $project->name }}</h1>
                <p class="text-sm text-gray-600">{{ __('projects.show.code') }}: <span class="font-semibold text-gray-800">{{ $project->code }}</span></p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('projects.index') }}"
                   class="inline-flex items-center gap-2 rounded-full border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-600 hover:border-gray-300 hover:text-gray-800 transition">← {{ __('projects.show.back') }}</a>
                <a href="{{ route('projects.statement', $project->id) }}"
                   class="inline-flex items-center gap-2 rounded-full bg-[#f8d147] px-5 py-2 text-sm font-semibold text-gray-900 shadow hover:bg-[#f6c626] transition">📄 {{ __('projects.show.statement') }}</a>
            </div>
        </div>
        <div class="grid gap-4 border-t border-gray-100 p-6 sm:grid-cols-2">
            <div class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                <p class="text-xs uppercase tracking-[0.3em] text-gray-500">{{ __('projects.show.total_floors') }}</p>
                <p class="mt-2 text-lg font-semibold text-gray-900">{{ $project->floors_count }}</p>
            </div>
            @if($project->notes)
                <div class="rounded-xl border border-[#f8d147]/30 bg-[#f8d147]/10 px-4 py-3 text-sm text-gray-800">
                    {{ $project->notes }}
                </div>
            @endif
        </div>
    </section>

    {{-- Units table --}}
    <section class="rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="flex flex-col gap-3 border-b border-gray-100 px-6 py-5 md:flex-row md:items-center md:justify-between">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('projects.show.units_table.title') }}</h2>
            <form method="GET" class="flex items-center gap-2">
                <label class="text-sm text-gray-500">{{ __('projects.show.units_table.per_page') }}</label>
                <select name="per_page" onchange="this.form.submit()" class="rounded-full border border-gray-300 bg-white px-3 py-1 text-sm focus:border-[#f8d147] focus:ring-[#f8d147]/40">
                    @php $pp = (int) request('per_page', 20); @endphp
                    @foreach([10,20,50,100] as $n)
                        <option value="{{ $n }}" {{ $pp === $n ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full table-auto text-sm">
                <thead>
                    <tr class="bg-[#f8d147]/25 text-left text-xs font-semibold uppercase tracking-[0.12em] text-gray-700">
                        <th class="px-4 py-3">{{ __('projects.show.units_table.headers.unit_code') }}</th>
                        <th class="px-4 py-3">{{ __('projects.show.units_table.headers.floor') }}</th>
                        <th class="px-4 py-3">{{ __('projects.show.units_table.headers.status') }}</th>
                        <th class="px-4 py-3">{{ __('projects.show.units_table.headers.price') }}</th>
                        <th class="px-4 py-3">{{ __('projects.show.units_table.headers.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white text-gray-700">
                    @forelse ($units as $unit)
                        <tr class="transition hover:bg-[#f8d147]/10">
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $unit->unit_code }}</td>
                            <td class="px-4 py-3">{{ $unit->floor->name ?? '-' }}</td>
                            <td class="px-4 py-3">
                                @switch($unit->status)
                                    @case('sold')
                                        <span class="inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">{{ __('common.status.sold') }}</span>
                                        @break
                                    @case('reserved')
                                        <span class="inline-flex rounded-full bg-[#f8d147]/25 px-3 py-1 text-xs font-semibold text-[#8f6b00]">{{ __('common.status.reserved') }}</span>
                                        @break
                                    @default
                                        <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">{{ __('common.status.available') }}</span>
                                @endswitch
                           </td>
                            <td class="px-4 py-3">
                                @if($unit->base_price)
                                    <span class="font-semibold text-gray-900">{{ number_format($unit->base_price, 2) }}</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('units.show', $unit->id) }}"
                                   class="inline-flex items-center gap-1 rounded-full border border-[#f8d147]/60 bg-[#f8d147]/30 px-3 py-1 text-xs font-semibold text-gray-800 hover:bg-[#f8d147]/40 transition">{{ __('projects.show.units_table.view') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-500">
                                {{ __('projects.show.units_table.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 px-6 py-4">
            {{ $units->links() }}
        </div>
    </section>

</div>
@endsection
