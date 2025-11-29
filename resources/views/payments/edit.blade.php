@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-6 py-10 bg-white rounded-xl shadow space-y-6">
    <h1 class="text-2xl font-bold text-[#1f2937]">Edit Payment #{{ $payment->id }}</h1>

    <div class="text-sm text-gray-700">
        <p><strong>Project:</strong> {{ $booking->unit->floor->project->name ?? '-' }}</p>
        <p><strong>Unit:</strong> {{ $booking->unit->unit_code ?? '-' }}</p>
        <p><strong>Customer:</strong> {{ $booking->customer->name ?? '-' }}</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 p-3 rounded">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $editInitialMethod = old('payment_method', $payment->payment_method);
        $editRequiresReference = in_array($editInitialMethod, ['bank_transfer', 'cheque'], true);
    @endphp

    <form method="POST" action="{{ route('payments.update', [$booking->id, $payment->id]) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            Invoice number is system-generated and cannot be changed. Update the reference code below if needed.
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Amount</label>
                <input type="number" step="0.01" name="amount" value="{{ old('amount', $payment->amount) }}" required class="mt-1 w-full border-gray-300 rounded shadow-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Paid At</label>
                <input type="datetime-local" name="paid_at" value="{{ old('paid_at', optional($payment->paid_at)->format('Y-m-d\TH:i')) }}" class="mt-1 w-full border-gray-300 rounded shadow-sm" />
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Invoice No.</label>
                <input type="text" value="{{ $payment->invoice_number ?? '—' }}" class="mt-1 w-full border-gray-200 bg-gray-100 text-gray-600 rounded shadow-sm" readonly />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Method</label>
                <select name="payment_method" class="mt-1 w-full border-gray-300 rounded shadow-sm" required
                        data-reference-group="#payment-reference-edit"
                        data-reference-required="bank_transfer,cheque">
                    @php($methods = ['cash'=>'Cash','bank_transfer'=>'Bank Transfer','cheque'=>'Cheque','card'=>'Card'])
                    @foreach($methods as $val=>$label)
                        <option value="{{ $val }}" {{ old('payment_method', $payment->payment_method) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Bank (Optional)</label>
                <input type="text" name="bank_name" value="{{ old('bank_name', $payment->bank_name) }}" class="mt-1 w-full border-gray-300 rounded shadow-sm" />
            </div>
            <div id="payment-reference-edit" class="{{ $editRequiresReference ? '' : 'hidden' }}">
                <label class="block text-sm font-medium text-gray-700">
                    Reference Code <span class="text-red-500 {{ $editRequiresReference ? '' : 'hidden' }}" data-reference-required-indicator>*</span>
                </label>
                <input type="text" name="reference_no" value="{{ old('reference_no', $payment->reference_no) }}"
                       class="mt-1 w-full border-gray-300 rounded shadow-sm"
                       {{ $editRequiresReference ? 'required' : '' }} />
                <p class="mt-1 text-xs text-gray-500">Required for bank transfers or cheques.</p>
                @error('reference_no')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Replace Receipt (PDF/JPG/PNG)</label>
            <input type="file" name="receipt" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 w-full text-sm text-gray-600" />
            @if($payment->receipt)
                <div class="mt-2 text-xs">
                    Current: <a href="{{ $payment->receipt }}" target="_blank" class="text-blue-600 underline">View</a>
                </div>
            @endif
        </div>

        <div class="pt-4 flex items-center gap-3">
            <button type="submit" class="bg-[#6b7280] hover:bg-[#4b5563] text-white px-6 py-2 rounded shadow">Save Changes</button>
            <a href="{{ route('payments.index', $booking->id) }}" class="text-gray-600 underline">Cancel</a>
        </div>
    </form>
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
