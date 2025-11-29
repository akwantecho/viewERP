@extends('layouts.app')

@section('content')
@php
    $banks = $banks ?? [
        'Bank Muscat','Bank Dhofar','National Bank of Oman','Sohar International',
        'Ahli Bank','Oman Arab Bank','HSBC Oman','Bank Nizwa','Alizz Islamic Bank'
    ];
@endphp

<div
    x-data="directSaleForm({
        vatRate: 0.05,
        unitPrice: {{ old('unit_price', $unit->base_price ?? 0) }},
        reservationDefault: @js(old('reservation_payment')),
        handoverDateDefault: @js(old('handover_due_date', now()->addMonths(1)->format('Y-m-d'))),
        customers: @js($customers ?? []),
        presetCustomerId: @js(old('customer_id')),
        paymentMethodDefault: @js(old('payment_method')),
        referenceDefault: @js(old('reference_no')),
    })"
    x-init="init()"
    class="max-w-5xl mx-auto py-10 px-4"
>
    @if (session('success'))
        <div class="mb-6 p-4 rounded-xl border border-green-300 bg-green-50 text-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl border border-red-300 bg-red-50 text-red-700">
            <ul class="list-disc ms-5">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('units.direct-sale.store', ['unit' => $unit->id]) }}"
        enctype="multipart/form-data"
        class="bg-white rounded-2xl shadow-lg border overflow-hidden"
    >
        @csrf

        <div class="px-6 py-5 border-b bg-[#f5f2e8]">
            <h1 class="text-2xl font-bold text-[#1f2937]">Direct Unit Sale</h1>
            <p class="text-sm text-[#626569] mt-1">
                Complete customer selection and payment details to record the direct sale of this unit.
            </p>
        </div>

        <div class="p-6 space-y-10">
            <section class="space-y-4">
                <h2 class="text-xl font-extrabold text-[#434141]">Project & Unit</h2>
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Project Name</label>
                        <input type="text" class="w-full border rounded-xl px-3 py-2"
                               value="{{ $unit->floor?->project?->name }}" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Project Code</label>
                        <input type="text" class="w-full border rounded-xl px-3 py-2"
                               value="{{ $unit->floor?->project?->code }}" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Unit Code</label>
                        <input type="text" class="w-full border rounded-xl px-3 py-2"
                               value="{{ $unit->unit_code }}" readonly>
                    </div>
                </div>
            </section>

            <hr>

            <section class="space-y-4">
                <h2 class="text-xl font-extrabold text-[#434141]">Customer</h2>
                <div>
                    <label class="block text-sm font-semibold mb-1">Select Customer</label>
                    <div class="relative" @click.outside="openList=false">
                        <input
                            x-model="query"
                            @input="filter()"
                            @focus="openList=true"
                            type="text"
                            placeholder="Search by name or Civil ID..."
                            class="w-full border rounded-xl px-3 py-2"
                        />
                        <input type="hidden" name="customer_id" :value="selected?.id ?? ''">

                        <div
                            x-show="openList"
                            x-transition
                            class="absolute z-10 mt-1 w-full bg-white border rounded-xl max-h-60 overflow-auto shadow-lg"
                        >
                            <template x-if="filtered.length === 0">
                                <div class="px-3 py-2 text-sm text-gray-500">No results</div>
                            </template>

                            <template x-for="c in filtered" :key="c.id">
                                <button type="button"
                                        @click="select(c); openList=false"
                                        class="w-full text-start px-3 py-2 hover:bg-gray-50"
                                >
                                    <div class="font-medium" x-text="c.name"></div>
                                    <div class="text-xs text-gray-500"
                                         x-text="'Civil ID: ' + (c.civil_number ?? '-')"></div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4 mt-3" x-show="selected">
                        <div>
                            <label class="block text-xs text-gray-500">Civil ID</label>
                            <div class="mt-1 bg-gray-50 border rounded-xl px-3 py-2"
                                 x-text="selected?.civil_number ?? '-'"></div>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Phone</label>
                            <div class="mt-1 bg-gray-50 border rounded-xl px-3 py-2"
                                 x-text="selected?.phone ?? '-'"></div>
                        </div>
                    </div>
                </div>
            </section>

            <hr>

            <section class="space-y-4">
                <h2 class="text-xl font-extrabold text-[#434141]">Sale Details</h2>
                <div>
                    <label class="block text-sm font-semibold mb-1">Sale Date</label>
                    <input
                        name="sale_date"
                        type="date"
                        value="{{ old('sale_date', now()->format('Y-m-d')) }}"
                        class="w-full border rounded-xl px-3 py-2"
                    >
                    @error('sale_date')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Total Price (incl. VAT)</label>
                        <input name="unit_price" type="number" step="0.01" min="0"
                               x-model.number="unitPrice"
                               @input="clampReservation()"
                               class="w-full border rounded-xl px-3 py-2" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">VAT (5%)</label>
                        <input type="text" class="w-full border rounded-xl px-3 py-2 bg-gray-50"
                               :value="formatCurrency(vatAmount)" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Base Price (excl. VAT)</label>
                        <input type="text" class="w-full border rounded-xl px-3 py-2 bg-gray-50"
                               :value="formatCurrency(basePrice)" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Reservation Payment</label>
                        <div class="flex">
                            <span class="inline-flex items-center rounded-l-xl border border-e-0 border-gray-300 bg-gray-100 px-3 text-sm text-gray-500">OMR</span>
                            <input name="reservation_payment" type="number" step="0.01" min="0"
                                   x-model.number="reservation"
                                   @input="clampReservation()"
                                   class="w-full border rounded-r-xl px-3 py-2" placeholder="0.00">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Amount collected now when the unit is reserved.</p>
                        @error('reservation_payment')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full border rounded-xl px-3 py-2"
                                x-model="paymentMethod">
                            <option value="">Select method</option>
                            <option value="cash" @selected(old('payment_method')==='cash')>Cash</option>
                            <option value="transfer" @selected(old('payment_method')==='transfer')>Bank Transfer</option>
                            <option value="cheque" @selected(old('payment_method')==='cheque')>Cheque</option>
                            <option value="card" @selected(old('payment_method')==='card')>Card</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Bank</label>
                        <select name="bank_name" class="w-full border rounded-xl px-3 py-2">
                            <option value="">Select bank</option>
                            @foreach ($banks as $bank)
                                <option value="{{ $bank }}" @selected(old('bank_name')===$bank)>{{ $bank }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div x-show="requiresReference" x-transition x-cloak>
                        <label class="block text-sm font-semibold mb-1">Reference Number</label>
                        <input name="reference_no" type="text" maxlength="100"
                               x-model.trim="reference"
                               :required="requiresReference"
                               value="{{ old('reference_no') }}"
                               class="w-full border rounded-xl px-3 py-2" placeholder="Enter reference code">
                        <p class="mt-1 text-xs text-gray-500">Required for bank transfers or cheques.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Upload Receipt</label>
                        <input name="receipt" type="file" accept="image/*,application/pdf"
                               class="w-full border rounded-xl px-3 py-2 file:me-3 file:py-2 file:px-3 file:border-0 file:rounded-lg file:bg-[#f5ce00] file:text-[#1f2937] file:cursor-pointer">
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Handover Due Date</label>
                        <input name="handover_due_date" type="date"
                               x-model="handoverDate"
                               class="w-full border rounded-xl px-3 py-2">
                        <p class="mt-1 text-xs text-gray-500">Scheduled date to collect the final handover payment.</p>
                        @error('handover_due_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1">Handover Payment (auto)</label>
                        <div class="flex">
                            <span class="inline-flex items-center rounded-l-xl border border-e-0 border-gray-300 bg-gray-100 px-3 text-sm text-gray-500">OMR</span>
                            <input type="text" readonly
                                   :value="handoverAmount.toFixed(2)"
                                   class="w-full border rounded-r-xl px-3 py-2 bg-gray-50 text-gray-700">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Calculated automatically from the total minus the reservation payment.</p>
                    </div>
                </div>
            </section>

            <section class="bg-gray-50 border rounded-2xl p-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Financial Summary</h3>
                <div class="grid sm:grid-cols-4 gap-4 text-sm">
                    <div class="bg-white border rounded-xl p-4 shadow-sm">
                        <p class="text-gray-500">Base Price</p>
                        <p class="text-lg font-bold text-[#1f2937]" x-text="formatCurrency(basePrice)"></p>
                    </div>
                    <div class="bg-white border rounded-xl p-4 shadow-sm">
                        <p class="text-gray-500">VAT (5%)</p>
                        <p class="text-lg font-bold text-[#1f2937]" x-text="formatCurrency(vatAmount)"></p>
                    </div>
                    <div class="bg-white border rounded-xl p-4 shadow-sm">
                        <p class="text-gray-500">Reservation Payment</p>
                        <p class="text-lg font-bold text-[#18ab69]" x-text="formatCurrency(reservation)"></p>
                    </div>
                    <div class="bg-white border rounded-xl p-4 shadow-sm">
                        <p class="text-gray-500">Handover Payment</p>
                        <p class="text-lg font-bold text-[#f97316]" x-text="formatCurrency(handoverAmount)"></p>
                    </div>
                </div>
            </section>
        </div>

        <div class="px-6 py-5 border-t bg-gray-50 flex justify-end">
            <button type="submit"
                    class="bg-[#18ab69] hover:bg-[#0d804f] text-white font-semibold px-6 py-2 rounded-xl shadow">
                Confirm Direct Sale
            </button>
        </div>
    </form>
</div>

<script>
function directSaleForm({ vatRate, unitPrice, reservationDefault, handoverDateDefault, customers, presetCustomerId, paymentMethodDefault, referenceDefault }) {
    return {
        customers,
        query: '',
        openList: false,
        filtered: [],
        selected: null,
        vatRate,
        unitPrice,
        reservation: 0,
        handoverDate: handoverDateDefault || '',
        paymentMethod: paymentMethodDefault || '',
        reference: referenceDefault || '',

        get vatAmount() { return round2(this.unitPrice * this.vatRate); },
        get basePrice() { return round2(this.unitPrice - this.vatAmount); },
        get handoverAmount() {
            const total = Number(this.unitPrice) || 0;
            const paid  = Number(this.reservation) || 0;
            return round2(Math.max(total - paid, 0));
        },

        get requiresReference() {
            return ['transfer', 'cheque'].includes(this.paymentMethod);
        },

        formatCurrency(v) {
            return new Intl.NumberFormat('en-OM', {
                style: 'currency', currency: 'OMR', minimumFractionDigits: 2
            }).format(v || 0);
        },

        clampReservation() {
            const total = Number(this.unitPrice) || 0;
            let paid = Number(this.reservation) || 0;
            if (paid < 0) paid = 0;
            if (paid > total) paid = total;
            this.reservation = round2(paid);
        },

        filter() {
            const q = (this.query || '').toLowerCase().trim();
            this.filtered = this.customers.filter(c =>
                (c.name || '').toLowerCase().includes(q) ||
                (String(c.civil_number || '')).includes(q) ||
                (String(c.phone || '')).includes(q)
            ).slice(0, 50);
        },

        select(c) {
            this.selected = c;
            this.query = c.name + ' — ' + (c.civil_number ?? '-');
        },

        init() {
            this.filter();
            const hasReservationDefault = reservationDefault !== null && reservationDefault !== undefined && reservationDefault !== '';
            const parsedReservation = Number(reservationDefault);
            this.reservation = hasReservationDefault && Number.isFinite(parsedReservation)
                ? round2(parsedReservation)
                : round2((Number(this.unitPrice) || 0) / 2);
            this.clampReservation();
            const presetId = presetCustomerId !== null && presetCustomerId !== undefined
                ? String(presetCustomerId)
                : null;
            if (presetId !== null) {
                const found = this.customers.find(c => String(c.id) === presetId);
                if (found) this.select(found);
            }
            this.paymentMethod = paymentMethodDefault || '';
            this.reference = referenceDefault || '';
            this.$watch('unitPrice', () => this.clampReservation());
            this.$watch('paymentMethod', (method) => {
                if (!['transfer', 'cheque'].includes(method)) {
                    this.reference = '';
                }
            });
        }
    };
}

function round2(n) {
    return Math.round((Number(n) || 0) * 100) / 100;
}
</script>
@endsection
