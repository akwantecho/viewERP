@extends('layouts.app')

@section('content')
@php
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Storage;

    $bookings = $customer->bookings ?? collect();
    $unitsCount = $bookings->count();
    $totalValue = 0;
    foreach ($bookings as $bookingItem) {
        $resolvedBookingTotal = (float) ($bookingItem->agreed_price ?? $bookingItem->total_price ?? (($bookingItem->agreed_base ?? $bookingItem->unit_price ?? 0) + ($bookingItem->agreed_vat ?? $bookingItem->vat ?? 0)));
        $totalValue += $resolvedBookingTotal;
    }

    $idUrl = null;
    if (!empty($customer->id_file)) {
        $idUrl = Str::startsWith($customer->id_file, ['http://','https://'])
            ? $customer->id_file
            : Storage::url($customer->id_file);
    }

    $agUrl = null;
    if (!empty($customer->agreement_file ?? null)) {
        $agUrl = Str::startsWith($customer->agreement_file, ['http://','https://'])
            ? $customer->agreement_file
            : Storage::url($customer->agreement_file);
    }

    $typeLabels = [
        'contract' => 'Contract',
        'id' => 'ID Document',
        'payment_receipt' => 'Payment Receipt',
        'other' => 'Other',
        null => 'Document',
        '' => 'Document',
    ];

    $resolveDocUrl = static function ($path) {
        if (empty($path)) {
            return null;
        }

        return Str::startsWith($path, ['http://','https://'])
            ? $path
            : Storage::url($path);
    };

    $documentEntries = collect();
    foreach ($bookings as $bookingDoc) {
        $unit = $bookingDoc->unit;
        if ($unit && $unit->documents) {
            foreach ($unit->documents as $doc) {
                $documentEntries->push([
                    'name' => $doc->name,
                    'label' => $typeLabels[$doc->type] ?? Str::headline((string) ($doc->type ?? 'Document')),
                    'url' => $resolveDocUrl($doc->path),
                    'related' => $unit->unit_code ? 'Unit '.$unit->unit_code : 'Unit #'.$unit->id,
                    'context' => 'Unit document',
                    'date' => optional($doc->created_at)->format('Y-m-d'),
                ]);
            }
        }

        $hasContractDoc = false;
        if ($bookingDoc->documents) {
            foreach ($bookingDoc->documents as $doc) {
                if ($doc->type === 'contract') {
                    $hasContractDoc = true;
                }

                $documentEntries->push([
                    'name' => $doc->name,
                    'label' => $typeLabels[$doc->type] ?? Str::headline((string) ($doc->type ?? 'Document')),
                    'url' => $resolveDocUrl($doc->path),
                    'related' => 'Booking #'.$bookingDoc->id,
                    'context' => 'Booking document',
                    'date' => optional($doc->created_at)->format('Y-m-d'),
                ]);
            }
        }

        if (!$hasContractDoc && $bookingDoc->contract_url) {
            $documentEntries->push([
                'name' => 'Booking Contract',
                'label' => $typeLabels['contract'],
                'url' => $bookingDoc->contract_url,
                'related' => 'Booking #'.$bookingDoc->id,
                'context' => 'Booking contract',
                'date' => optional($bookingDoc->created_at)->format('Y-m-d'),
            ]);
        }
    }

    if ($customer->documents) {
        foreach ($customer->documents as $doc) {
            $documentEntries->push([
                'name' => $doc->name,
                'label' => $typeLabels[$doc->type] ?? Str::headline((string) ($doc->type ?? 'Document')),
                'url' => $doc->path,
                'related' => 'Customer profile',
                'context' => 'Customer document',
                'date' => optional($doc->created_at)->format('Y-m-d'),
            ]);
        }
    }

    if ($idUrl) {
        $documentEntries->push([
            'name' => 'Civil ID',
            'label' => 'ID Document',
            'url' => $idUrl,
            'related' => 'Customer profile',
            'context' => 'Uploaded file',
            'date' => null,
        ]);
    }

    if ($agUrl) {
        $documentEntries->push([
            'name' => 'Agreement',
            'label' => 'Contract',
            'url' => $agUrl,
            'related' => 'Customer profile',
            'context' => 'Uploaded file',
            'date' => null,
        ]);
    }

    $documentEntries = $documentEntries->sortByDesc('date')->values();
@endphp

<div class="space-y-6 px-4 py-8 lg:px-10">
    <div class="overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-600 via-emerald-500 to-green-500 p-8 text-white shadow-lg">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <p class="text-sm font-medium uppercase tracking-wide text-white/70">{{ __('customers.profile.badge') }}</p>
                <h1 class="mt-2 text-3xl font-semibold">{{ $customer->name }}</h1>
                <div class="mt-4 flex flex-wrap gap-3 text-sm">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2 5.5A2.5 2.5 0 014.5 3h11A2.5 2.5 0 0118 5.5v9a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 012 14.5v-9zM4.5 4.5a1 1 0 00-1 1v.654l6.637 3.989a1 1 0 001.043 0L17 6.154V5.5a1 1 0 00-1-1h-11z" />
                        </svg>
                        {{ $customer->email ?? __('customers.profile.email_missing') }}
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3.5 2A1.5 1.5 0 002 3.5v1.192c0 .748.341 1.455.93 1.934l2.02 1.667a2.75 2.75 0 002.979.34l.627-.313a1 1 0 011.115.164l2.275 2.133a1 1 0 01.28.934l-.278 1.113a2.75 2.75 0 001.882 3.314l1.155.33a1.25 1.25 0 001.577-.902l.558-2.23A3.75 3.75 0 0015.37 9.5l-1.83-.488a2.75 2.75 0 01-1.626-1.148l-.796-1.193A4.75 4.75 0 007.63 5H6.25a1.75 1.75 0 01-1.75-1.75V3.5A1.5 1.5 0 003.5 2z" clip-rule="evenodd" />
                        </svg>
                        <a href="tel:{{ $customer->phone }}" class="hover:underline">{{ $customer->phone ?? __('customers.profile.phone_missing') }}</a>
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 1.75a6.75 6.75 0 016.75 6.75c0 3.855-2.45 6.98-4.798 8.937a7.45 7.45 0 01-3.904 1.588 7.45 7.45 0 01-3.904-1.588C5.7 15.48 3.25 12.355 3.25 8.5A6.75 6.75 0 0110 1.75zm0 9.25a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" clip-rule="evenodd" />
                        </svg>
                        {{ $customer->coming_from ?? __('customers.profile.source_missing') }}
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 2a6 6 0 00-6 6v1.5l-.707 2.121A1 1 0 004.243 13H15.757a1 1 0 00.95-1.379L16 9.5V8a6 6 0 00-6-6zm2.293 13.707a1 1 0 00-1.414-1.414 2 2 0 01-2.758 0 1 1 0 10-1.414 1.414 4 4 0 005.586 0z" clip-rule="evenodd" />
                        </svg>
                        {{ __('customers.profile.civil_id') }}: {{ $customer->civil_number ?? '—' }}
                    </span>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <div class="grid gap-4 text-right sm:grid-cols-2">
                    <div class="rounded-2xl bg-white/15 p-5">
                        <p class="text-sm text-white/70">{{ __('customers.profile.reserved_units') }}</p>
                        <p class="mt-2 text-3xl font-semibold">{{ $unitsCount }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/15 p-5">
                        <p class="text-sm text-white/70">{{ __('customers.profile.total_value') }}</p>
                        <p class="mt-2 text-3xl font-semibold">{{ number_format($totalValue, 2) }}</p>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('customers.notes.index', $customer) }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-white/20 hover:bg-white/30 px-4 py-2 text-sm font-semibold text-white backdrop-blur-sm transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Notes
                    </a>
                    @can('customers.manage')
                    <a href="{{ route('customers.edit', $customer) }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-white/20 hover:bg-white/30 px-4 py-2 text-sm font-semibold text-white backdrop-blur-sm transition">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Customer
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <section id="overview" class="space-y-6">
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('customers.profile.contact.title') }}</h2>
                <dl class="mt-5 grid gap-4 text-sm text-gray-600">
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('customers.profile.contact.name') }}</dt>
                        <dd class="mt-1 text-gray-900">{{ $customer->name }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('customers.profile.contact.phone') }}</dt>
                        <dd class="mt-1">
                            @if($customer->phone)
                                <a href="tel:{{ $customer->phone }}" class="text-emerald-600 hover:underline">{{ $customer->phone }}</a>
                            @else
                                <span class="text-gray-400">{{ __('customers.profile.contact.not_provided') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('customers.profile.contact.email') }}</dt>
                        <dd class="mt-1">{{ $customer->email ?? __('customers.profile.contact.not_provided') }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('customers.profile.contact.coming_from') }}</dt>
                        <dd class="mt-1">{{ $customer->coming_from ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-500">{{ __('customers.profile.contact.id_document') }}</dt>
                        <dd class="mt-1">
                            @if($idUrl)
                                <a href="{{ $idUrl }}" target="_blank" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700 hover:bg-emerald-100">
                                    {{ __('customers.profile.contact.download') }}
                                </a>
                            @else
                                <span class="text-gray-400">{{ __('customers.profile.contact.not_uploaded') }}</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-emerald-900">{{ __('customers.profile.notes.title') }}</h2>
                @if($notes->count())
                    @php $recentNote = $notes->first(); @endphp
                    <p class="mt-4 text-sm text-emerald-800">
                        <strong>{{ $recentNote->title ?: __('customers.profile.notes.form.title') }}</strong>
                        <span class="text-emerald-700/80">&middot; {{ $recentNote->updated_at?->diffForHumans() ?? __('common.general.recently') }}</span>
                    </p>
                    <p class="mt-3 text-sm text-emerald-900/80">
                        {{ Str::limit($recentNote->text ?? '', 160) ?: __('customers.profile.notes.empty') }}
                    </p>
                @else
                    <p class="mt-4 text-sm text-emerald-800">
                        {{ __('customers.profile.notes.empty') }}
                    </p>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('customers.profile.bookings.title') }}</h2>
                    <p class="text-sm text-gray-500">{{ __('customers.profile.bookings.subtitle') }}</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">{{ $unitsCount }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm text-gray-700">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('customers.profile.bookings.headers.project') }}</th>
                            <th class="px-6 py-3">{{ __('customers.profile.bookings.headers.unit') }}</th>
                            <th class="px-6 py-3">{{ __('customers.profile.bookings.headers.floor') }}</th>
                            <th class="px-6 py-3">{{ __('customers.profile.bookings.headers.date') }}</th>
                            <th class="px-6 py-3">{{ __('customers.profile.bookings.headers.price') }}</th>
                            <th class="px-6 py-3">{{ __('customers.profile.bookings.headers.status') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('customers.profile.bookings.headers.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($bookings as $booking)
                            @php
                                $unit = $booking->unit;
                                $project = $unit?->floor?->project;
                            @endphp
                            <tr class="transition hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-900">{{ $project->name ?? '—' }}</span>
                                        @if($project && $project->location)
                                            <span class="text-xs text-gray-500">{{ $project->location }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-medium text-gray-900">{{ $unit->unit_code ?? '—' }}</span>
                                    <p class="text-xs text-gray-500">{{ __('common.entities.unit') }} ID: {{ $unit->id ?? 'N/A' }}</p>
                                </td>
                                <td class="px-6 py-4">{{ $unit?->floor?->name ?? '—' }}</td>
                                <td class="px-6 py-4">{{ optional($booking->reservation_date)->format('Y-m-d') ?? '—' }}</td>
                                @php
                                    $profileUnitBase = (float) ($booking->agreed_base ?? $booking->unit_price ?? 0);
                                @endphp
                                <td class="px-6 py-4">{{ number_format($profileUnitBase, 2) }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-semibold
                                        @if($unit?->status === 'sold') bg-emerald-50 text-emerald-700
                                        @elseif($unit?->status === 'reserved') bg-amber-50 text-amber-700
                                        @else bg-gray-100 text-gray-600
                                        @endif">
                                        {{ __('common.status.' . ($unit?->status ?? 'pending')) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @if($unit)
                                        <a href="{{ route('units.show', $unit->id) }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-500">
                                            {{ __('customers.profile.bookings.view_unit') }}
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400">{{ __('customers.profile.bookings.unit_removed') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-400">{{ __('customers.profile.bookings.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('customers.profile.documents.title') }}</h2>
                    <p class="text-sm text-gray-500">{{ __('customers.profile.documents.description') }}</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">{{ $documentEntries->count() }}</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm text-gray-700">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-6 py-3">{{ __('customers.profile.documents.name') }}</th>
                            <th class="px-6 py-3">{{ __('customers.profile.documents.label') }}</th>
                            <th class="px-6 py-3">{{ __('customers.profile.documents.related') }}</th>
                            <th class="px-6 py-3">{{ __('customers.profile.documents.context') }}</th>
                            <th class="px-6 py-3">{{ __('customers.profile.documents.date') }}</th>
                            <th class="px-6 py-3 text-right">{{ __('customers.profile.documents.link') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($documentEntries as $doc)
                            <tr class="transition hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $doc['name'] }}</td>
                                <td class="px-6 py-4">{{ $doc['label'] }}</td>
                                <td class="px-6 py-4">{{ $doc['related'] }}</td>
                                <td class="px-6 py-4 text-gray-500">{{ $doc['context'] }}</td>
                                <td class="px-6 py-4">{{ $doc['date'] ? $doc['date'] : '—' }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if(!empty($doc['url']))
                                        <a href="{{ $doc['url'] }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-black/85">
                                            {{ __('customers.profile.documents.link') }}
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-400">{{ __('customers.profile.documents.unavailable') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-400">{{ __('customers.profile.documents.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

@push('scripts')
<script>
(function () {
    const buttons = document.querySelectorAll('.tab-button');
    const panels = document.querySelectorAll('[data-tab-panel]');
    if (!buttons.length || !panels.length) return;

    function activateTab(target) {
        buttons.forEach((btn) => {
            const isActive = btn.dataset.tabToggle === target;
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            btn.classList.toggle('bg-gray-900/90', false);
            btn.classList.toggle('text-white', false);
            if (isActive) {
                btn.classList.add('bg-emerald-600', 'text-white');
                btn.classList.remove('text-gray-500');
            } else {
                btn.classList.remove('bg-emerald-600', 'text-white');
                btn.classList.add('text-gray-500');
            }
        });

        panels.forEach((panel) => {
            panel.classList.toggle('hidden', panel.dataset.tabPanel !== target);
        });
    }

    function detectInitialTab() {
        const hash = window.location.hash.replace('#', '');
        const valid = hash && Array.from(panels).some((panel) => panel.dataset.tabPanel === hash);
        return valid ? hash : 'overview';
    }

    const initialTab = detectInitialTab();
    activateTab(initialTab);

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = btn.dataset.tabToggle;
            activateTab(target);
            const newHash = '#' + target;
            if (history.replaceState) {
                history.replaceState(null, '', newHash);
            } else {
                window.location.hash = newHash;
            }
        });
    });

    window.addEventListener('hashchange', () => {
        const tab = detectInitialTab();
        activateTab(tab);
    });
})();
</script>
@endpush
@endsection
