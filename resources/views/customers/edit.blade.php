@extends('layouts.app')

@section('content')
@php
    $idTypes = collect(explode(',', (string)($customer->id_type ?? '')))->filter()->values()->all();
    $selectedTypes = collect(old('id_type', $idTypes));
@endphp

<div class="max-w-5xl mx-auto space-y-6">
    <div class="rounded-3xl border border-gray-100 bg-white/90 p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-center gap-4">
                <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                        <path d="M12 14.25a4.125 4.125 0 100-8.25 4.125 4.125 0 000 8.25z" />
                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75a9.718 9.718 0 01-2.612 6.652 11.944 11.944 0 01-2.602 2.232 48.551 48.551 0 01-4.536 2.647.75.75 0 01-.69 0 48.551 48.551 0 01-4.536-2.647 11.944 11.944 0 01-2.602-2.232A9.718 9.718 0 012.25 12zm9.75-7.25a7.25 7.25 0 00-7.25 7.25 8.218 8.218 0 002.216 5.597 10.48 10.48 0 002.148 1.852A47.031 47.031 0 0012 21.68a47.034 47.034 0 002.886-1.231 10.48 10.48 0 002.148-1.852A8.218 8.218 0 0019.25 12a7.25 7.25 0 00-7.25-7.25z" clip-rule="evenodd" />
                    </svg>
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-emerald-600">{{ __('customers.index.hero.badge') }}</p>
                    <h2 class="text-2xl font-bold text-gray-900">{{ __('customers.form.edit_title') }}</h2>
                </div>
            </div>
            <p class="text-sm text-gray-500">{{ __('customers.form.helpers.required_hint') }}</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-sm text-red-700">
            <strong class="font-semibold">{{ __('customers.form.messages.errors_title') }}</strong>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('customers.update', $customer) }}" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-gray-100 bg-white/95 p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-emerald-600">{{ __('customers.form.sections.contact') }}</p>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('customers.profile.contact.title') }}</h3>
                    <p class="text-sm text-gray-500">{{ __('customers.form.sections.contact_hint') }}</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">1/2</span>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">{{ __('customers.form.fields.name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" required
                           class="w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm shadow-sm focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200"
                           placeholder="{{ __('customers.form.placeholders.name') }}">
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">{{ __('customers.form.fields.phone') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" required
                           class="w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm shadow-sm focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200"
                           placeholder="{{ __('customers.form.placeholders.phone') }}">
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">{{ __('customers.form.fields.email') }}</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                           class="w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm shadow-sm focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200"
                           placeholder="{{ __('customers.form.placeholders.email') }}">
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">{{ __('customers.form.fields.coming_from') }}</label>
                    <input type="text" name="coming_from" value="{{ old('coming_from', $customer->coming_from) }}"
                           class="w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm shadow-sm focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200"
                           placeholder="{{ __('customers.form.placeholders.coming_from') }}">
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">{{ __('customers.form.fields.civil_number') }}</label>
                    <input type="text" name="civil_number" value="{{ old('civil_number', $customer->civil_number) }}"
                           class="w-full rounded-2xl border border-gray-200 px-4 py-3 text-sm shadow-sm focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200"
                           placeholder="{{ __('customers.form.placeholders.civil_number') }}">
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-100 bg-white/95 p-6 shadow-sm">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-emerald-600">{{ __('customers.form.sections.identity') }}</p>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('customers.profile.contact.id_document') }}</h3>
                    <p class="text-sm text-gray-500">{{ __('customers.form.sections.identity_hint') }}</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">2/2</span>
            </div>

            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-3">
                    <label class="text-sm font-medium text-gray-700">{{ __('customers.form.fields.id_type') }}</label>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 rounded-full border border-gray-200 px-3 py-1 text-sm text-gray-700">
                            <input type="checkbox" name="id_type[]" value="immigrant"
                                   @checked($selectedTypes->contains('immigrant'))
                                   class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            {{ __('customers.form.id_type_options.immigrant') }}
                        </label>
                        <label class="flex items-center gap-2 rounded-full border border-gray-200 px-3 py-1 text-sm text-gray-700">
                            <input type="checkbox" name="id_type[]" value="passport"
                                   @checked($selectedTypes->contains('passport'))
                                   class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            {{ __('customers.form.id_type_options.passport') }}
                        </label>
                    </div>
                </div>
                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700">{{ __('customers.form.fields.id_upload') }}</label>
                    <input type="file" name="id_file"
                           class="w-full rounded-2xl border border-dashed border-gray-300 px-4 py-3 text-sm focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200">
                    <p class="text-xs text-gray-500">{{ __('customers.form.helpers.id_upload') }}</p>
                </div>
            </div>
        </section>

        <div class="flex flex-col gap-4 rounded-2xl border border-gray-100 bg-white/95 p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h4 class="text-base font-semibold text-gray-900">{{ __('customers.form.edit_title') }}</h4>
                <p class="text-sm text-gray-500">{{ __('customers.form.sections.contact_hint') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('customers.index') }}" class="inline-flex items-center rounded-2xl border border-gray-200 px-5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50">{{ __('buttons.cancel') }}</a>
                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-6 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    {{ __('customers.form.buttons.update') }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
