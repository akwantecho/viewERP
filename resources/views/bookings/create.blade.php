@extends('layouts.app')

@section('content')
@php
    $banks = $banks ?? [
        'Bank Muscat','Bank Dhofar','National Bank of Oman','Sohar International',
        'Ahli Bank','Oman Arab Bank','HSBC Oman','Bank Nizwa','Alizz Islamic Bank'
    ];
@endphp

<div
    x-data="bookingForm({
        vatRate: {{ config('app.vat_rate', 0.05) }},
        unitPrice: {{ old('unit_price', $unit->base_price ?? 0) }},
        advance: {{ old('advance_payment', 0) }},
        customers: @js($customers ?? []),
        presetCustomerId: {{ old('customer_id', 'null') }},
        paymentMethodDefault: @js(old('payment_method')),
        referenceDefault: @js(old('reference_no')),
    })"
    class="max-w-6xl mx-auto py-10 px-4"
>

    {{-- Flash / Errors --}}
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
        id="booking-form"
        method="POST"
        action="{{ route('units.booking.store', ['unit' => $unit->id]) }}"
        enctype="multipart/form-data"
        class="bg-white rounded-2xl shadow-lg border overflow-hidden"
    >
        @csrf

        {{-- Header --}}
        <div class="px-6 py-5 border-b bg-[#f5f2e8]">
            <h1 class="text-2xl font-bold text-[#1f2937]">Unit Booking</h1>
            <p class="text-sm text-[#626569] mt-1">
                Fill customer details, payments, installments, and documents, then review the financial summary.
            </p>
        </div>

        <div class="p-6 space-y-10">

            {{-- Section 1: Project / Unit / Customer --}}
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
                    <div>
                        <label class="block text-sm font-semibold mb-1">Reservation Date</label>
                        <input
                            name="reservation_date"
                            type="date"
                            value="{{ old('reservation_date', now()->format('Y-m-d')) }}"
                            class="w-full border rounded-xl px-3 py-2"
                        >
                        @error('reservation_date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Customer search --}}
                <div class="mt-4">
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

                        {{-- Results --}}
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

                    {{-- Selected customer --}}
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

            {{-- Section 2: Price & Advance --}}
            <section class="space-y-4">
                <h2 class="text-xl font-extrabold text-[#434141]">Price & Advance</h2>

                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Unit Price (incl. VAT)</label>
                        <input name="unit_price" type="number" step="0.01" min="0"
                               x-model.number="unitPrice"
                               class="w-full border rounded-xl px-3 py-2" placeholder="0.00">
                        <p class="text-xs text-gray-500 mt-1">
                            Base price reference: OMR {{ number_format($unit->base_price ?? 0, 2) }}
                        </p>
                        @error('unit_price')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">VAT (5%)</label>
                        <input type="text" class="w-full border rounded-xl px-3 py-2 bg-gray-50"
                               :value="formatCurrency(vatAmount)" readonly>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Advance Payment</label>
                        <input name="advance_payment" type="number" step="0.01" min="0"
                               x-model.number="advance"
                               class="w-full border rounded-xl px-3 py-2" placeholder="0.00">
                        <p class="text-xs text-gray-500 mt-1">Deducted from total, not part of installments.</p>
                        @error('advance_payment')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                 <div>
            <label class="block text-sm font-semibold mb-1">Payment Method</label>
            <select name="payment_method" class="w-full border rounded-xl px-3 py-2"
                    x-model="paymentMethod">
                <option value="">Select method</option>
                <option value="cash"     @selected(old('payment_method')==='cash')>Cash</option>
                <option value="transfer" @selected(old('payment_method')==='transfer')>Bank Transfer</option>
                <option value="cheque"   @selected(old('payment_method')==='cheque')>Cheque</option>
                <option value="card"     @selected(old('payment_method')==='card')>Card</option>
            </select>
        </div>

                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Bank</label>
                        <select name="bank_name" class="w-full border rounded-xl px-3 py-2">
                            <option value="">Select bank</option>
                            @foreach($banks as $b)
                                <option value="{{ $b }}" @selected(old('bank_name')===$b)>{{ $b }}</option>
                            @endforeach
                        </select>
                    </div>

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
                        <label class="block text-sm font-semibold mb-1">Upload Receipt (S3)</label>
                        <input name="receipt" type="file" accept="image/*,application/pdf"
                               class="w-full border rounded-xl px-3 py-2 file:me-3 file:py-2 file:px-3 file:border-0 file:rounded-lg file:bg-[#f5ce00] file:text-[#1f2937] file:cursor-pointer">
                    </div>
                </div>

                <div class="rounded-2xl border border-dashed border-amber-400 bg-amber-50 p-4 text-sm text-amber-800">
                    Installment schedules are configured after booking. Submit this form first, then continue to the
                    Installment Plan page to build or regenerate the payment plan.
                </div>
            </section>

            {{-- Section 4: Financial Summary --}}
            <section class="space-y-4">
                <h2 class="text-xl font-extrabold text-[#434141]">Financial Summary</h2>

                <div class="grid md:grid-cols-3 gap-4">
                    <div class="bg-[#f5f2e8] border rounded-2xl p-4">
                        <div class="flex justify-between text-sm">
                            <span>Unit Price (excl. VAT)</span>
                            <span class="font-bold" x-text="formatCurrency(unitPrice - vatAmount)"></span>
                        </div>
                        <div class="flex justify-between text-sm mt-2">
                            <span>VAT (5%)</span>
                            <span class="font-bold" x-text="formatCurrency(vatAmount)"></span>
                        </div>
                        <div class="flex justify-between text-sm mt-2">
                            <span>Total (incl. VAT)</span>
                            <span class="font-bold" x-text="formatCurrency(unitPrice)"></span>
                        </div>
                    </div>

                    <div class="bg-[#f5f2e8] border rounded-2xl p-4">
                        <div class="flex justify-between text-sm">
                            <span>Advance Payment</span>
                            <span class="font-bold" x-text="formatCurrency(advance)"></span>
                        </div>
                        <div class="flex justify-between text-sm mt-2">
                            <span>Remaining</span>
                            <span class="font-bold" x-text="formatCurrency(remaining)"></span>
                        </div>
                    </div>

                    <div class="bg-[#f5f2e8] border rounded-2xl p-4">
                        <p class="text-sm font-semibold text-gray-700">Next Step</p>
                        <p class="mt-2 text-sm text-gray-600">
                            Installment plans are configured on a dedicated page. After saving this booking you will be redirected
                            to build or regenerate the payment schedule.
                        </p>
                    </div>
                </div>
            </section>

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-4">
                <a href="{{ route('units.show', $unit->id) }}"
                   class="px-4 py-2 rounded-xl border text-[#434141] hover:bg-gray-50">Cancel</a>
                <button type="submit"
                        class="px-6 py-2 rounded-xl bg-[#6b7280] text-white shadow hover:opacity-90">
                    Save Booking
                </button>
            </div>
        </div>
    </form>
</div>

<script>
const ARABIC_DIGIT_MAP = {
    '٠': '0','١': '1','٢': '2','٣': '3','٤': '4','٥': '5','٦': '6','٧': '7','٨': '8','٩': '9',
    '۰': '0','۱': '1','۲': '2','۳': '3','۴': '4','۵': '5','۶': '6','۷': '7','۸': '8','۹': '9',
};
const ARABIC_DIGIT_REGEX = /[٠-٩۰-۹]/g;

function round2(n) {
    return Math.round((Number(n) || 0) * 100) / 100;
}

function bookingForm({ vatRate, unitPrice, advance, customers, presetCustomerId, paymentMethodDefault, referenceDefault }) {
    return {
        // الداتا الخام من الباك
        customersRaw: customers || [],

        // اللي نشتغل عليها بعد إزالة التكرار
        customers: [],
        query: '',
        openList: false,
        filtered: [],
        selected: null,
        vatRate,
        unitPrice,
        advance,
        paymentMethod: paymentMethodDefault || '',
        reference: referenceDefault || '',

        get vatAmount() {
            return round2(this.unitPrice * this.vatRate);
        },
        get remaining() {
            return round2(Math.max(this.unitPrice - this.advance, 0));
        },
        get requiresReference() {
            return ['transfer', 'cheque'].includes(this.paymentMethod);
        },

        normalizeDigits(value) {
            return String(value ?? '')
                .replace(ARABIC_DIGIT_REGEX, (char) => ARABIC_DIGIT_MAP[char] ?? '')
                .replace(/\D+/g, '');
        },

        identityKey(c) {
            if (!c) return '';
            const civil = this.normalizeDigits(c.civil_number);
            if (civil) return `civil-${civil}`;
            const phone = this.normalizeDigits(c.phone);
            if (phone) return `phone-${phone}`;
            return `id-${c.id}`;
        },

        formatCurrency(v) {
            return new Intl.NumberFormat('en-OM', {
                style: 'currency',
                currency: 'OMR',
                minimumFractionDigits: 2,
            }).format(v || 0);
        },

        filter() {
            const q = (this.query || '').toLowerCase().trim();
            const matches = [];

            for (const c of this.customers) {
                if (
                    (c.name || '').toLowerCase().includes(q) ||
                    String(c.civil_number || '').includes(q) ||
                    String(c.phone || '').includes(q)
                ) {
                    matches.push(c);
                    if (matches.length >= 50) break;
                }
            }

            this.filtered = matches;
        },

        select(c) {
            this.selected = c;
            this.query = c.name + ' — ' + (c.civil_number ?? '-');
        },

        init() {
            // 1) إزالة التكرار مرة واحدة في البداية
            const seen = new Set();
            this.customers = this.customersRaw.filter((c) => {
                const key = this.identityKey(c) || `id-${c.id}`;
                if (seen.has(key)) {
                    return false; // مكرر → تجاهله
                }
                seen.add(key);
                return true;
            });

            console.log('🔥 customersRaw length:', this.customersRaw.length);
            console.log('✅ customers unique length:', this.customers.length);

            // 2) أول فلترة (query فاضي)
            this.filter();

            // 3) اختيار عميل محفوظ من old('customer_id') إن وجد
            if (presetCustomerId) {
                const found = this.customers.find(c => c.id === presetCustomerId);
                if (found) this.select(found);
            }

            // 4) تهيئة قيم الدفع
            this.paymentMethod = paymentMethodDefault || '';
            this.reference = referenceDefault || '';

            this.$watch('paymentMethod', (method) => {
                if (!['transfer', 'cheque'].includes(method)) {
                    this.reference = '';
                }
            });
        },
    };
}
</script>
@endsection

