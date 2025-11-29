@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-6">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Payment</p>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">
                Add Payment
                <span class="block text-sm font-normal text-slate-500">
                    {{ $installment ? 'Installment #' . $installment->installment_number : 'Advance / General' }}
                </span>
            </h1>
            <dl class="mt-5 grid gap-3 sm:grid-cols-3 text-sm text-slate-600">
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Project</dt>
                    <dd class="mt-1 text-slate-900">{{ $booking->unit->floor->project->name ?? '-' }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unit</dt>
                    <dd class="mt-1 text-slate-900">{{ $booking->unit->unit_code ?? '-' }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Customer</dt>
                    <dd class="mt-1 text-slate-900">{{ $booking->customer->name ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <div class="px-6 py-6 space-y-6">
            @if ($errors->any())
                <div class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-600">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($installment)
                @php($alreadyPaid = $installment->payments()->sum('amount'))
                <div class="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-4 text-sm text-emerald-900">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Installment overview</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg bg-white/70 px-3 py-2">
                            <p class="text-xs text-emerald-700">Total</p>
                            <p class="font-semibold">OMR {{ number_format($installment->total_amount, 2) }}</p>
                        </div>
                        <div class="rounded-lg bg-white/70 px-3 py-2">
                            <p class="text-xs text-emerald-700">Paid</p>
                            <p class="font-semibold">OMR {{ number_format($alreadyPaid, 2) }}</p>
                        </div>
                        <div class="rounded-lg bg-white/70 px-3 py-2">
                            <p class="text-xs text-emerald-700">Remaining</p>
                            <p class="font-semibold">OMR {{ number_format(max(0, $installment->total_amount - $alreadyPaid), 2) }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <span class="font-semibold text-slate-800">Heads up:</span>
                Invoice numbers are assigned automatically after saving. Make sure the reference matches your bank or POS record.
            </div>

            @php
                $requiresReference = in_array(old('payment_method'), ['bank_transfer', 'cheque'], true);
            @endphp

            <form method="POST"
                  action="{{ $installment
                                ? route('payments.store.installment', [$booking->id, $installment->id])
                                : route('payments.store', $booking->id) }}"
                  enctype="multipart/form-data"
                  class="space-y-5">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="amount" class="text-sm font-medium text-slate-700">Amount</label>
                        <div class="mt-1 flex">
                            <span class="inline-flex items-center rounded-l-lg border border-slate-200 bg-slate-100 px-3 text-sm text-slate-500">OMR</span>
                            <input type="number" name="amount" id="amount" step="0.01" min="0"
                                   value="{{ old('amount') }}"
                                   class="block w-full rounded-r-lg border border-l-0 border-slate-200 bg-white py-2.5 px-3 text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                   required>
                        </div>
                    </div>

                    <div>
                        <label for="paid_at" class="text-sm font-medium text-slate-700">Payment Date</label>
                        <input type="date" name="paid_at" id="paid_at"
                               value="{{ old('paid_at', now()->format('Y-m-d')) }}"
                               class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2.5 px-3 text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="payment_method" class="text-sm font-medium text-slate-700">Payment Method</label>
                        <select name="payment_method" id="payment_method"
                                class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2.5 px-3 text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                data-reference-group="#payment-reference"
                                data-reference-required="bank_transfer,cheque"
                                required>
                            <option value="" disabled {{ old('payment_method') ? '' : 'selected' }}>Select method</option>
                            <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="cheque" {{ old('payment_method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="card" {{ old('payment_method') === 'card' ? 'selected' : '' }}>Card</option>
                        </select>
                    </div>

                    <div>
                        <label for="bank_name" class="text-sm font-medium text-slate-700">Bank (Optional)</label>
                        <input type="text" name="bank_name" id="bank_name"
                               value="{{ old('bank_name') }}"
                               placeholder="Bank Muscat"
                               class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2.5 px-3 text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                    </div>
                </div>

                <div id="payment-reference" class="{{ $requiresReference ? '' : 'hidden' }}">
                    <label for="reference_no" class="text-sm font-medium text-slate-700">
                        Reference Code <span class="text-red-500 {{ $requiresReference ? '' : 'hidden' }}" data-reference-required-indicator>*</span>
                    </label>
                    <input type="text" name="reference_no" id="reference_no"
                           value="{{ old('reference_no') }}"
                           placeholder="e.g. TX123456"
                           class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2.5 px-3 text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                           {{ $requiresReference ? 'required' : '' }}>
                    <p class="mt-1 text-xs text-slate-500">Required for bank transfers or cheques. Hidden for other methods.</p>
                    @error('reference_no')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="receipt" class="text-sm font-medium text-slate-700">Upload Receipt</label>
                    <div class="mt-1 flex items-center gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        <div class="flex-1">
                            <p>PDF, JPG or PNG — max 2 MB</p>
                        </div>
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-700 hover:bg-slate-100">
                            Browse
                            <input type="file" name="receipt" id="receipt" accept=".pdf,.jpg,.jpeg,.png" class="hidden">
                        </label>
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2 focus:ring-offset-white">
                        <span class="text-base">💰</span>
                        Save Payment
                    </button>
                    <a href="{{ route('payments.index', $booking->id) }}"
                       class="text-sm font-medium text-slate-500 transition hover:text-slate-700">Cancel</a>
                </div>
            </form>
</div>
</div>
</div>

@push('scripts')
    @once
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-reference-group]').forEach(function (selectEl) {
                    var targetSelector = selectEl.getAttribute('data-reference-group');
                    if (!targetSelector) return;

                    var wrapper = document.querySelector(targetSelector);
                    if (!wrapper) return;

                    var input = wrapper.querySelector('input[name="reference_no"]');
                    var indicator = wrapper.querySelector('[data-reference-required-indicator]');
                    var requiredList = (selectEl.getAttribute('data-reference-required') || 'bank_transfer,cheque')
                        .split(',')
                        .map(function (v) { return v.trim(); })
                        .filter(function (v) { return v.length > 0; });

                    var toggle = function () {
                        var needsReference = requiredList.indexOf(selectEl.value) !== -1;
                        wrapper.classList.toggle('hidden', !needsReference);

                        if (input) {
                            input.required = needsReference;
                            if (!needsReference) {
                                input.value = '';
                            }
                        }

                        if (indicator) {
                            indicator.classList.toggle('hidden', !needsReference);
                        }
                    };

                    toggle();
                    selectEl.addEventListener('change', toggle);
                });
            });
        </script>
    @endonce
@endpush
@endsection
