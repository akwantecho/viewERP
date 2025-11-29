@extends('layouts.app')

@section('content')
    <div class="space-y-5">
        <div class="overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 to-green-500 px-6 py-5 text-white shadow">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm/5 uppercase tracking-wide text-white/70">{{ __('customers.index.hero.badge') }}</p>
                    <h2 class="mt-1 text-2xl font-semibold">{{ __('customers.index.hero.title') }}</h2>
                </div>

                <div class="flex items-center gap-3 text-right">
                    <span class="text-sm text-white/80">{{ __('customers.index.hero.total') }}</span>
                    <span class="rounded-full bg-white/15 px-4 py-2 text-lg font-semibold">
                        {{ number_format($customers->total()) }}
                    </span>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="flex flex-col gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <form method="GET" action="{{ route('customers.index') }}" class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                <label class="relative flex items-center">
                    <span class="pointer-events-none absolute left-3 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.5 3a5.5 5.5 0 103.473 9.713l3.657 3.657a.75.75 0 101.06-1.06l-3.657-3.657A5.5 5.5 0 008.5 3zm-4 5.5a4 4 0 118 0 4 4 0 01-8 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <input
                        type="text"
                        name="q"
                        value="{{ $q ?? '' }}"
                        placeholder="{{ __('customers.index.search.placeholder') }}"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 pl-11 pr-4 py-2 text-sm shadow-inner focus:border-emerald-500 focus:bg-white focus:outline-none focus:ring focus:ring-emerald-100"
                    />
                </label>

                <div class="flex gap-2 sm:justify-end">
                    <button class="inline-flex items-center gap-2 rounded-xl bg-gray-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-black/85">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M3.5 4.75A2.75 2.75 0 016.25 2h7.5A2.75 2.75 0 0116.5 4.75v1.5a.75.75 0 01-1.5 0v-1.5c0-.69-.56-1.25-1.25-1.25h-7.5c-.69 0-1.25.56-1.25 1.25v10.5c0 .69.56 1.25 1.25 1.25h7.5c.69 0 1.25-.56 1.25-1.25v-1.5a.75.75 0 011.5 0v1.5A2.75 2.75 0 0113.75 18h-7.5A2.75 2.75 0 013.5 15.25V4.75z" />
                            <path d="M14.28 10.53a.75.75 0 010-1.06l2.47-2.47a.75.75 0 011.28.53v4.94a.75.75 0 01-1.28.53l-2.47-2.47z" />
                        </svg>
                        {{ __('customers.index.search.button') }}
                    </button>

                    @if(!empty($q))
                        <a href="{{ route('customers.index') }}" class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                            {{ __('customers.index.search.clear') }}
                        </a>
                    @endif
                </div>
            </form>

            <a href="{{ route('customers.create') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3.5a.75.75 0 01.75.75V9.25H15a.75.75 0 010 1.5h-4.25V15a.75.75 0 01-1.5 0v-4.25H5a.75.75 0 010-1.5h4.25V4.25A.75.75 0 0110 3.5z" clip-rule="evenodd" />
                </svg>
                {{ __('customers.index.buttons.create') }}
            </a>
        </div>

        @if(!empty($q))
            <p class="text-sm text-gray-500">
                {!! __('customers.index.search.results', ['term' => '<span class="font-semibold text-gray-700">“'.e($q).'”</span>']) !!}
            </p>
        @endif

        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm text-gray-700">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th scope="col" class="px-5 py-3">{{ __('customers.index.table.customer') }}</th>
                            <th scope="col" class="px-5 py-3">{{ __('customers.index.table.civil_id') }}</th>
                            <th scope="col" class="px-5 py-3">{{ __('customers.index.table.phone') }}</th>
                            <th scope="col" class="px-5 py-3 text-right">{{ __('customers.index.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($customers as $customer)
                            <tr class="transition hover:bg-gray-50">
                                <td class="px-5 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-gray-900">{{ $customer->name }}</span>
                                        @if(!empty($customer->email))
                                            <span class="text-xs text-gray-500">{{ $customer->email }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                        {{ $customer->civil_number ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4">
                                    <a href="tel:{{ $customer->phone }}" class="text-gray-800 hover:text-emerald-600">
                                        {{ $customer->phone }}
                                    </a>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('customers.profile', $customer) }}"
                                           class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 transition hover:border-emerald-200 hover:text-emerald-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10 2a5 5 0 00-3.536 8.536L6 17a.75.75 0 00.97.727l3.03-.86 3.03.86A.75.75 0 0014 17l-.464-6.464A5 5 0 0010 2z" />
                                            </svg>
                                            {{ __('customers.index.table.view') }}
                                        </a>
                                        <a href="{{ route('customers.edit', $customer) }}"
                                           class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M4.75 13.5a.75.75 0 01.75-.75h5.379l-6.22 6.22a.75.75 0 01-1.28-.53v-4.94z" />
                                                <path d="M16.243 3.757a2.5 2.5 0 00-3.536 0L6.44 10.024a1.75 1.75 0 00-.464.813l-.726 2.54a.75.75 0 00.928.928l2.54-.726c.305-.087.58-.255.813-.464l6.268-6.268a2.5 2.5 0 000-3.536z" />
                                            </svg>
                                            {{ __('customers.index.table.edit') }}
                                        </a>
                                        <form method="POST" action="{{ route('customers.destroy', $customer) }}"
                                              data-confirm="delete" data-confirm-title="{{ __('customers.index.table.confirm_title') }}" data-confirm-message="{{ __('customers.index.table.confirm_message') }}" class="inline">
                                            @csrf @method('DELETE')
                                            <button class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium text-red-600 transition hover:bg-red-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M8.75 3a1.75 1.75 0 00-1.743 1.608L6.964 5h6.072l-.043-.392A1.75 1.75 0 0011.25 3h-2.5zm6.5 2.5H4.75l.732 8.79A2.75 2.75 0 008.22 18h3.56a2.75 2.75 0 002.738-3.71L15.25 5.5zM3.5 5.5a.75.75 0 000 1.5h13a.75.75 0 000-1.5h-13z" clip-rule="evenodd" />
                                                </svg>
                                                {{ __('customers.index.table.delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-gray-400">
                                    {{ __('customers.index.table.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t border-gray-100 px-5 py-4 text-sm text-gray-500">
                <div>
                    {{ __('customers.index.pagination.showing', ['count' => $customers->count(), 'total' => $customers->total()]) }}
                </div>
                <div class="[&>div]:inline-flex [&>div]:items-center [&>div]:gap-2 [&>div>span]:hidden sm:[&>div>span]:inline-flex">
                    {{ $customers->onEachSide(1)->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
