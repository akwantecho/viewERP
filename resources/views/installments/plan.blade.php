@extends('layouts.app')

@section('content')
<div class="w-full px-6 py-10 space-y-8"
     x-data="planBuilder({
        defaultPlanType: @js(old('plan_type', $planDefaults['plan_type'])),
        defaultRows: @js(old('custom_installments', $customDefaults)),
    })">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('installments.plan.title', ['code' => $unit->unit_code]) }}</h1>
            <p class="text-sm text-gray-600">
                {{ __('installments.plan.subtitle', [
                    'project' => $unit->floor?->project?->name,
                    'customer' => $customer?->name,
                ]) }}
            </p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('installments.index', $booking->id) }}"
               class="rounded-xl border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">{{ __('installments.plan.back') }}</a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($hasInstallmentPayments)
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800">
            {{ __('installments.plan.warning') }}
        </div>
    @endif
    @php
        $selectedPlanType = old('plan_type', $planDefaults['plan_type']);
    @endphp
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <ul class="list-disc space-y-1 ps-4">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <p class="text-sm text-gray-500">{{ __('installments.plan.stats.total') }}</p>
            <p class="text-2xl font-bold text-gray-900">OMR {{ number_format($booking->total_price, 2) }}</p>
        </div>
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <p class="text-sm text-gray-500">{{ __('installments.plan.stats.advance') }}</p>
            <p class="text-2xl font-bold text-gray-900">OMR {{ number_format($booking->advance_payment, 2) }}</p>
        </div>
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <p class="text-sm text-gray-500">{{ __('installments.plan.stats.remaining') }}</p>
            <p class="text-2xl font-bold text-amber-600">OMR {{ number_format($remainingAmount, 2) }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('bookings.installments.plan.store', $booking->id) }}" class="space-y-8 rounded-2xl border bg-white p-6 shadow" id="plan-builder">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700">{{ __('installments.plan.form.start_date') }}</label>
            <input type="date" name="start_date"
                   value="{{ old('start_date', $planDefaults['start_date']) }}"
                   class="mt-2 w-full rounded-xl border px-3 py-2">
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700">{{ __('installments.plan.form.plan_type') }}</label>
            <div class="mt-2 flex gap-3">
                @foreach (['fixed' => __('installments.plan.form.plan_type_options.fixed'), 'custom' => __('installments.plan.form.plan_type_options.custom')] as $value => $label)
                    <label class="flex items-center gap-2 rounded-2xl border px-4 py-2 text-sm cursor-pointer"
                           :class="planType === '{{ $value }}' ? 'border-green-500 bg-green-50 text-green-700' : 'border-gray-200 text-gray-600'">
                        <input type="radio" name="plan_type" value="{{ $value }}" class="hidden" x-model="planType" @checked($selectedPlanType === $value)>
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div x-show="planType === 'fixed'" x-cloak class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-semibold text-gray-700">{{ __('installments.plan.form.installments_count') }}</label>
                    <input type="number" min="1" name="installments_count"
                           value="{{ old('installments_count', $planDefaults['installments_count']) }}"
                           class="mt-1 w-full rounded-xl border px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700">{{ __('installments.plan.form.monthly_due_day') }}</label>
                    <input type="number" min="1" max="31" name="monthly_due_day"
                           value="{{ old('monthly_due_day', $planDefaults['monthly_due_day']) }}"
                           class="mt-1 w-full rounded-xl border px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700">{{ __('installments.plan.form.frequency') }}</label>
                    <input type="number" min="1" max="12" name="installment_frequency"
                           value="{{ old('installment_frequency', $planDefaults['installment_frequency']) }}"
                           class="mt-1 w-full rounded-xl border px-3 py-2">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700">{{ __('installments.plan.form.installment_value') }}</label>
                <input type="number" step="0.01" min="0" name="installment_value"
                       value="{{ old('installment_value') }}"
                       class="mt-1 w-full rounded-xl border px-3 py-2"
                       placeholder="{{ __('installments.plan.form.installment_value_placeholder') }}">
            </div>
        </div>

        <div x-show="planType === 'custom'" x-cloak class="space-y-4">
            <template x-for="(row, index) in rows" :key="row.key">
                <div class="grid items-end gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700">{{ __('installments.plan.form.custom_due_date') }}</label>
                        <input type="date" class="mt-1 w-full rounded-xl border px-3 py-2"
                               :name="`custom_installments[${index}][due_date]`" x-model="row.due_date">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700">{{ __('installments.plan.form.custom_total') }}</label>
                        <input type="number" min="0" step="0.01"
                               class="mt-1 w-full rounded-xl border px-3 py-2"
                               :name="`custom_installments[${index}][total_amount]`" x-model.number="row.total_amount">
                    </div>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-sm font-semibold text-gray-700">{{ __('installments.plan.form.custom_number') }}</label>
                            <input type="number" min="1"
                                   class="mt-1 w-full rounded-xl border px-3 py-2"
                                   :name="`custom_installments[${index}][number]`" x-model.number="row.number">
                        </div>
                        <button type="button"
                                class="mt-6 rounded-xl border border-red-200 px-3 py-2 text-sm text-red-600 hover:bg-red-50"
                                @click="removeRow(index)">{{ __('installments.plan.form.custom_remove') }}</button>
                    </div>
                </div>
            </template>
            <div class="flex flex-wrap items-center gap-3">
                <button type="button"
                        class="rounded-full border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        @click="addRow()">{{ __('installments.plan.form.custom_add') }}</button>
                <div class="text-xs" :class="rowsTotalDiff === 0 ? 'text-gray-500' : 'text-amber-600'">
                    {{ __('installments.plan.form.custom_total_label') }} <span class="font-semibold" x-text="formatCurrency(rowsTotal)"></span>
                    {{ __('installments.plan.form.custom_diff') }} <span class="font-semibold" x-text="formatCurrency(rowsTotalDiff)"></span>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-500">{{ __('installments.plan.form.save_notice') }}</p>
            <button type="submit" class="rounded-xl bg-[#0f5d32] px-6 py-2 text-sm font-semibold text-white shadow hover:bg-[#0c4a27]">
                {{ __('installments.plan.form.save_button') }}
            </button>
        </div>
    </form>

    @if ($existingPlan->isNotEmpty())
        <div class="rounded-2xl border bg-white p-6 shadow">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('installments.plan.current_preview') }}</h2>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 text-gray-600">
                        <tr>
                            <th class="px-3 py-2 text-start">{{ __('installments.plan.preview_headers.number') }}</th>
                            <th class="px-3 py-2 text-start">{{ __('installments.plan.preview_headers.due_date') }}</th>
                            <th class="px-3 py-2 text-end">{{ __('installments.plan.preview_headers.total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($existingPlan as $row)
                            <tr class="border-b last:border-0">
                                <td class="px-3 py-2">{{ $row['number'] }}</td>
                                <td class="px-3 py-2">{{ $row['due_date'] ?? '-' }}</td>
                                <td class="px-3 py-2 text-end">{{ number_format($row['total'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function planBuilder({ defaultPlanType, defaultRows }) {
    return {
        planType: defaultPlanType || 'fixed',
        rows: Array.isArray(defaultRows) && defaultRows.length ? defaultRows.map((row, idx) => ({
            key: Date.now() + idx,
            number: row.number ?? (idx + 1),
            due_date: row.due_date ?? '',
            total_amount: row.total_amount ?? row.total ?? '',
        })) : [{ key: Date.now(), number: 1, due_date: '', total_amount: '' }],
        addRow() {
            this.rows.push({ key: Date.now() + Math.random(), number: this.rows.length + 1, due_date: '', total_amount: '' });
        },
        removeRow(index) {
            if (this.rows.length <= 1) return;
            this.rows.splice(index, 1);
        },
        get rowsTotal() {
            return this.rows.reduce((sum, row) => sum + (Number(row.total_amount) || 0), 0);
        },
        get rowsTotalDiff() {
            return round2({{ $remainingAmount }} - this.rowsTotal);
        },
        formatCurrency(value) {
            return new Intl.NumberFormat('en-OM', { style: 'currency', currency: 'OMR', minimumFractionDigits: 2 }).format(value || 0);
        },
        init() {
            if (!this.planType) {
                this.planType = 'fixed';
            }
        }
    };
}
function round2(n) { return Math.round((Number(n) || 0) * 100) / 100; }
</script>
@endpush
