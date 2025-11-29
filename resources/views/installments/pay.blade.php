@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-6">
            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Installment Payment</p>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900">
                Pay Installment #{{ $installment->installment_number }}
                <span class="block text-sm font-normal text-slate-500">Booking #{{ $installment->booking_id }}</span>
            </h1>

            @php
                $alreadyPaid = $installment->payments()->sum('amount');
                $remainingAmount = max(0, $installment->total_amount - $alreadyPaid);
            @endphp

            <dl class="mt-5 grid gap-3 sm:grid-cols-4 text-sm text-slate-600">
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Base Amount</dt>
                    <dd class="mt-1 text-slate-900">OMR {{ number_format($installment->amount, 2) }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">VAT (5%)</dt>
                    <dd class="mt-1 text-slate-900">OMR {{ number_format($installment->vat, 2) }}</dd>
                </div>
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Paid</dt>
                    <dd class="mt-1 text-slate-900">OMR {{ number_format($alreadyPaid, 2) }}</dd>
                </div>
                <div class="rounded-xl bg-emerald-50 px-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Remaining</dt>
                    <dd class="mt-1 text-emerald-900 font-semibold">OMR {{ number_format($remainingAmount, 2) }}</dd>
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

            <div class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <span class="font-semibold text-slate-800">Reminder:</span>
                Reference codes should line up with your bank or POS details. Partial payments are allowed up to the remaining balance.
            </div>

            @php
                $omaniBanks = [
                    'Bank Muscat',
                    'National Bank of Oman (NBO)',
                    'BankDhofar',
                    'Sohar International',
                    'Oman Arab Bank',
                    'Ahli Bank',
                    'HSBC Bank Oman',
                    'Bank Nizwa',
                    'Alizz Islamic Bank',
                    'Meethaq Islamic Banking (Bank Muscat)',
                    'Muzn Islamic Banking (NBO)',
                    'Maisarah Islamic Banking (BankDhofar)',
                    'Sohar Islamic (Sohar International)',
                    'Ahli Islamic (Ahli Bank)',
                    'Al Yusr Islamic Banking (Oman Arab Bank)',
                    'Other / Not listed',
                ];
                $selectedBank = old('bank_name');
            @endphp

            <form method="POST"
                  action="{{ route('payments.store.installment', [$installment->booking_id, $installment->id]) }}"
                  enctype="multipart/form-data"
                  class="space-y-5">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="amount" class="text-sm font-medium text-slate-700">Amount to Pay Now</label>
                        <div class="mt-1 flex">
                            <span class="inline-flex items-center rounded-l-lg border border-slate-200 bg-slate-100 px-3 text-sm text-slate-500">OMR</span>
                            <input type="number" step="0.01" min="0" max="{{ $remainingAmount }}"
                                   name="amount" id="amount"
                                   value="{{ old('amount', $remainingAmount > 0 ? number_format($remainingAmount, 2, '.', '') : null) }}"
                                   class="block w-full rounded-r-lg border border-l-0 border-slate-200 bg-white py-2.5 px-3 text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                   required>
                        </div>
                    </div>

                    <div>
                        <label for="paid_at" class="text-sm font-medium text-slate-700">Payment Date</label>
                        <input type="date" name="paid_at" id="paid_at"
                               value="{{ old('paid_at', now()->format('Y-m-d')) }}"
                               class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2.5 px-3 text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                               required>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="payment_method" class="text-sm font-medium text-slate-700">Payment Method</label>
                        <select name="payment_method" id="payment_method"
                                class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2.5 px-3 text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                required>
                            <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="cheque" {{ old('payment_method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="card" {{ old('payment_method') === 'card' ? 'selected' : '' }}>Card</option>
                        </select>
                    </div>

                    <div>
                        <label for="bank_name" class="text-sm font-medium text-slate-700">Bank (Optional)</label>
                        <select name="bank_name" id="bank_name"
                                class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2.5 px-3 text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                            <option value="">-- Select Bank --</option>
                            @foreach ($omaniBanks as $bank)
                                <option value="{{ $bank }}" {{ $selectedBank === $bank ? 'selected' : '' }}>
                                    {{ $bank }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="reference_no" class="text-sm font-medium text-slate-700">Reference Code <span class="text-red-500">*</span></label>
                    <input type="text" name="reference_no" id="reference_no"
                           value="{{ old('reference_no') }}"
                           placeholder="e.g. TX123456"
                           class="mt-1 block w-full rounded-lg border border-slate-200 bg-white py-2.5 px-3 text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                           required>
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
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-offset-2 focus:ring-offset-white">
                        <span class="text-base">💰</span>
                        Confirm Payment
                    </button>
                    <a href="{{ route('payments.index', $installment->booking_id) }}"
                       class="text-sm font-medium text-slate-500 transition hover:text-slate-700">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
