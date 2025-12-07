<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\Customer;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;
use Illuminate\Validation\Rule;
use App\Models\Document;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Http\Controllers\Concerns\NormalizesReferenceInput;
use App\Services\InvoiceNumberGenerator;

class BookingController extends Controller
{
    use NormalizesReferenceInput;

    public function __construct(private InvoiceNumberGenerator $invoiceNumbers)
    {
    }

    /**
     * عرض نموذج حجز وحدة
     */
    public function form($id)
    {
        $unit = Unit::with('floor.project')->findOrFail($id);

        if ($unit->status !== 'available') {
            return redirect()
                ->route('units.show', $unit->id)
                ->with('error', 'الوحدة غير متاحة للحجز.');
        }

        $digitMap = [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ];

        $normalizeDigits = static function (?string $value) use ($digitMap): string {
            if ($value === null) {
                return '';
            }

            $converted = strtr($value, $digitMap);
            $digitsOnly = preg_replace('/[^0-9]/', '', $converted ?? '');

            return $digitsOnly ?? '';
        };

        $customers = Customer::select('id','name','civil_number','phone')
            ->orderBy('name')
            ->get()
            ->unique(function ($customer) use ($normalizeDigits) {
                $normalizeField = static function (?string $value) use ($normalizeDigits): string {
                    $digits = $normalizeDigits($value);
                    if ($digits !== '') {
                        return $digits;
                    }

                    $trimmed = trim((string) $value);

                    return $trimmed !== '' ? mb_strtolower($trimmed, 'UTF-8') : '';
                };

                if ($civil = $normalizeField($customer->civil_number)) {
                    return 'civil-'.$civil;
                }

                if ($phone = $normalizeField($customer->phone)) {
                    return 'phone-'.$phone;
                }

                return 'id-'.$customer->id;
            })
            ->values();

        return view('bookings.create', compact('unit', 'customers'));
    }

    /**
     * تخزين الحجز + تسجيل العربون (إن وُجد)
     * يتم إنشاء الأقساط في خطوة لاحقة عبر صفحة الخطة.
     */
    public function store(Request $request, Unit $unit)
    {
        $unit->loadMissing('floor.project');
        $request->merge([
            'reference_no' => $this->normalizeReferenceInput($request),
        ]);

        $rules = [
            'unit_price'         => ['nullable', 'numeric', 'min:1'],
            'advance_payment'    => ['nullable','numeric','min:0'],
            'customer_id'        => ['required','exists:customers,id'],
            'reservation_date'   => ['required','date'],
            'payment_method'     => [
                Rule::requiredIf((float) $request->input('advance_payment', 0) > 0),
                'string','max:100'
            ],
            'bank_name'          => ['nullable','string','max:100'],
            'reference_no'       => ['required','string','max:100'],
            'receipt'            => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:2048'],
            'contract_file'      => ['required','file','mimes:pdf,jpg,jpeg,png','max:20480'],
        ];

        // التحقق
        $validated = $request->validate($rules);

        // 1) الحسابات الأساسية
        $vatRate      = (float) config('app.vat_rate', 0.05);
        $basePriceRaw = (float) ($unit->base_price ?? 0);
        if ($basePriceRaw <= 0) {
            $fallbackTotal = (float) ($validated['unit_price'] ?? $request->input('unit_price', 0));
            if ($fallbackTotal > 0) {
                $basePriceRaw = round($fallbackTotal / (1 + $vatRate), 2);
            }
        }
        if ($basePriceRaw <= 0) {
            return back()->withErrors([
                'unit_price' => 'السعر الأساسي للوحدة غير مهيأ بعد.'
            ])->withInput();
        }

        $agreedBase  = round($basePriceRaw, 2);
        $agreedVat   = round($agreedBase * $vatRate, 2);
        // VAT is informational only; do not add to totals
        $agreedPrice = $agreedBase;
        $totalPrice  = $agreedBase;
        $vatTotal    = $agreedVat;

        $advanceInput = isset($validated['advance_payment'])
            ? round((float) $validated['advance_payment'], 2)
            : 0.0;

        if ($advanceInput > $agreedPrice) {
            return back()->withErrors([
                'advance_payment' => 'الدفعة الأولى لا يمكن أن تتجاوز السعر الكلي.'
            ])->withInput();
        }

        $advance    = $advanceInput;
        $remaining  = round(max($agreedPrice - $advance, 0), 2);
        $reservationDate = Carbon::parse($validated['reservation_date'])->startOfDay();


        // تجهيز قيم الدفع (مع افتراضي آمن)
        $paymentMethod = $validated['payment_method'] ?? 'cash';
        // Always keep provided bank/reference if the user entered them (even for cash)
        $bankName      = $validated['bank_name'] ?? null;
        $referenceNo   = $validated['reference_no'] ?? null;

        // Prepare safe codes once
        $projectCode = strtoupper((string) optional($unit->floor->project)->code);
        $unitCode    = strtoupper((string) $unit->unit_code);
        $safeProject = preg_replace('/[^A-Za-z0-9\\-_.]+/', '-', $projectCode ?: 'PROJECT');
        $safeUnit    = preg_replace('/[^A-Za-z0-9\\-_.]+/', '-', $unitCode ?: ('UNIT-'.$unit->id));
        $diskDefault = config('filesystems.disks.s3') ? 's3' : 'public';

        // 1. Upload contract (required)
        $contractPath = null;
        $contractUrl  = null;
        if ($request->hasFile('contract_file')) {
            try {
                $contractDir = "bookings/{$safeProject}/{$safeUnit}/contracts";
                $contractExt = strtolower($request->file('contract_file')->getClientOriginalExtension() ?: 'pdf');
                $contractBase = 'CONTRACT-'.$safeUnit;
                $candidate = $contractBase.'.'.$contractExt;
                $c = 1;
                try {
                    while (Storage::disk($diskDefault)->exists("$contractDir/$candidate") && $c < 50) {
                        $candidate = $contractBase.'-'.$c.'.'.$contractExt;
                        $c++;
                    }
                } catch (\Throwable $e) {
                    \Log::error('Unhandled exception while checking booking contract collision', [
                        'location' => __METHOD__,
                        'class'    => static::class,
                        'unit_id'  => $unit->id ?? null,
                        'dir'      => $contractDir,
                        'message'  => $e->getMessage(),
                        'trace'    => $e->getTraceAsString(),
                    ]);

                    report($e);
                }

                $storedContract = $request->file('contract_file')->storePubliclyAs($contractDir, $candidate, $diskDefault);
                $contractPath = $storedContract;
                try {
                    $contractUrl = Storage::disk($diskDefault)->url($storedContract);
                } catch (\Throwable $e) {
                    \Log::error('Unhandled exception while generating booking contract URL', [
                        'location'  => __METHOD__,
                        'class'     => static::class,
                        'unit_id'   => $unit->id ?? null,
                        'path'      => $storedContract,
                        'message'   => $e->getMessage(),
                        'trace'     => $e->getTraceAsString(),
                    ]);

                    report($e);
                    $contractUrl = null;
                }
            } catch (\Throwable $e) {
                \Log::error('Unhandled exception while processing booking contract upload', [
                    'location' => __METHOD__,
                    'class'    => static::class,
                    'unit_id'  => $unit->id ?? null,
                    'message'  => $e->getMessage(),
                    'trace'    => $e->getTraceAsString(),
                ]);

                report($e);
                $contractPath = null;
                $contractUrl  = null;
            }
        }

        if (!$contractPath) {
            return back()
                ->withErrors(['contract_file' => 'فشل رفع العقد، يرجى المحاولة مرة أخرى.'])
                ->withInput();
        }

        // 2) رفع إيصال العربون إلى S3 إن توفّر، وإلا public
        $receiptUrl = null; // full URL for viewing
        $receiptPath = null; // relative path saved into payments
        if ($request->hasFile('receipt')) {
            try {
                // Organized storage: bookings/{PROJECT_CODE}/{UNIT_CODE}/payments
                $dir  = "bookings/{$safeProject}/{$safeUnit}/payments";
                $ext  = strtolower($request->file('receipt')->getClientOriginalExtension() ?: 'pdf');
                $base = $referenceNo ? preg_replace('/[^A-Za-z0-9\-_.]+/', '-', (string) $referenceNo) : ('ADV-'.$unit->id);
                $base = trim($base, '-');
                $candidate = $base . '.' . $ext;
                $i = 1; $nameToUse = $candidate;
                try {
                    while (Storage::disk($diskDefault)->exists("$dir/$nameToUse") && $i < 50) {
                        $nameToUse = $base.'-'.$i.'.'.$ext; $i++;
                    }
                } catch (\Throwable $e) {
                    \Log::error('Unhandled exception while checking existing booking receipt', [
                        'location'  => __METHOD__,
                        'class'     => static::class,
                        'unit_id'   => $unit->id ?? null,
                        'dir'       => $dir,
                        'message'   => $e->getMessage(),
                        'trace'     => $e->getTraceAsString(),
                    ]);

                    report($e);
                }
                $storedRel = $request->file('receipt')->storePubliclyAs($dir, $nameToUse, $diskDefault);
                // Optional optimize if local public
                if ($diskDefault === 'public') {
                    try { ImageOptimizer::optimize(storage_path('app/public/'.$storedRel)); } catch (\Throwable $e) {
                        \Log::error('Unhandled exception while optimizing booking receipt image', [
                            'location'  => __METHOD__,
                            'class'     => static::class,
                            'unit_id'   => $unit->id ?? null,
                            'path'      => $storedRel,
                            'message'   => $e->getMessage(),
                            'trace'     => $e->getTraceAsString(),
                        ]);

                        report($e);
                    }
                }
                $receiptPath = $storedRel;
                try { $receiptUrl = Storage::disk($diskDefault)->url($storedRel); } catch (\Throwable $e) {
                    \Log::error('Unhandled exception while generating booking receipt URL', [
                        'location'  => __METHOD__,
                        'class'     => static::class,
                        'unit_id'   => $unit->id ?? null,
                        'path'      => $storedRel,
                        'message'   => $e->getMessage(),
                        'trace'     => $e->getTraceAsString(),
                    ]);

                    report($e);

                    $receiptUrl = null;
                }
            } catch (\Throwable $e) {
                \Log::error('Unhandled exception while processing booking receipt upload', [
                    'location' => __METHOD__,
                    'class'    => static::class,
                    'unit_id'  => $unit->id ?? null,
                    'message'  => $e->getMessage(),
                    'trace'    => $e->getTraceAsString(),
                ]);

                report($e);
                $receiptPath = null;
                $receiptUrl = null;
            }
        }

        // 3) تنفيذ العملية داخل معاملة واحدة
        try {
            DB::beginTransaction();

            // قفل السجل لعدم حدوث تعارضات
            $unit = Unit::with('floor')->lockForUpdate()->findOrFail($unit->id);

            if ($unit->status !== 'available') {
                DB::rollBack();
                return redirect()
                    ->route('units.show', $unit->id)
                    ->with('error', 'الوحدة لم تعد متاحة للحجز.');
            }

            $customer = Customer::findOrFail($validated['customer_id']);

            // تغيير حالة الوحدة
            $unit->update(['status' => 'reserved']);

            // إنشاء الحجز
            $booking = Booking::create([
                'unit_id'            => $unit->id,
                'customer_id'        => $customer->id,
                'project_id'         => $unit->floor->project_id,
                'reservation_date'   => $reservationDate,
                'unit_price'         => $agreedBase,
                'agreed_base'        => $agreedBase,
                'agreed_vat'         => $agreedVat,
                'agreed_price'       => $agreedPrice,
                'vat'                => $vatTotal,
                'total_price'        => $totalPrice,
                'advance_payment'    => $advance,
                'advance_amount'     => $advance,
                'remaining_amount'   => $remaining,
                'installments_count' => 0,
                'installment_frequency' => 0,
                'monthly_due_day'    => null,
                'plan_type'          => null,
                'status'             => 'confirmed',
                'contract_file'      => $contractPath,
            ]);

            // Attach contract as document entry for project/unit pages & customer profile
            if (Schema::hasTable('documents')) {
                try {
                    Document::create([
                        'name'              => 'Booking Contract',
                        'type'              => 'contract',
                        'path'              => $contractUrl ?? $contractPath,
                        'documentable_type' => Booking::class,
                        'documentable_id'   => $booking->id,
                    ]);
                } catch (\Throwable $e) {
                    \Log::error('Unhandled exception while creating booking contract document', [
                        'location'   => __METHOD__,
                        'class'      => static::class,
                        'booking_id' => $booking->id ?? null,
                        'unit_id'    => $unit->id ?? null,
                        'path'       => $contractPath,
                        'message'    => $e->getMessage(),
                        'trace'      => $e->getTraceAsString(),
                    ]);

                    report($e);
                }
            }

            // تسجيل العربون كدفعة عامة (غير مرتبطة بقسط)
            if ($advance > 0) {
                // إذا لم يُرسل reference_no ولّد واحد تلقائيًا PROJECT-UNIT-SEQ
                $seq = max(1, (int) Payment::where('booking_id', $booking->id)->count() + 1);
                $unitCode    = strtoupper((string) $unit->unit_code);
                $autoRef     = trim($unitCode.'-'.$seq, '-');

                Payment::create([
                    'booking_id'     => $booking->id,
                    'invoice_number' => $this->invoiceNumbers->next(),
                    'installment_id' => null,           // null = عربون/دفعة عامة
                    'amount'         => $advance,
                    'payment_method' => $paymentMethod, // مهم: ليس null
                    'bank_name'      => $bankName,
                    'reference_no'   => $referenceNo ?: $autoRef,
                    'receipt'        => $receiptPath,
                    'paid_at'        => $reservationDate,
                ]);

                // حفظ المستند ضمن نظام المستندات لسهولة العرض داخل صفحة مستندات الوحدة
                if ($receiptUrl && Schema::hasTable('documents')) {
                    Document::create([
                        'name'               => 'Advance Payment Receipt',
                        'type'               => 'payment_receipt',
                        'path'               => $receiptUrl,
                        'documentable_type'  => Booking::class,
                        'documentable_id'    => $booking->id,
                    ]);
                }
            }

            DB::commit();

            // log activity
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('activities')) {
                    \App\Models\Activity::create([
                        'user_id' => optional($request->user())->id,
                        'action' => 'booking.create',
                        'entity_type' => Booking::class,
                        'entity_id' => $booking->id,
                        'meta' => [
                            'unit_id' => $unit->id,
                            'total_price' => $totalPrice,
                            'advance' => $advance,
                        ],
                        'ip' => $request->ip(),
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::error('Unhandled exception while logging booking.create activity', [
                    'location'   => __METHOD__,
                    'class'      => static::class,
                    'booking_id' => $booking->id ?? null,
                    'unit_id'    => $unit->id ?? null,
                    'message'    => $e->getMessage(),
                    'trace'      => $e->getTraceAsString(),
                ]);

                report($e);
            }

        return redirect()
            ->route('bookings.installments.plan', $booking->id)
            ->with('success', 'تم حفظ بيانات الحجز. قم بمتابعة إعداد خطة الأقساط.')
            ->with('plan_cta', true);

        } catch (\Throwable $e) {
            DB::rollBack();

            // محاولة إعادة الوحدة إلى متاحة (لو تغيّرت حالتها)
            try {
                $unit->refresh();
                if ($unit->status === 'reserved') {
                    $unit->update(['status' => 'available']);
                }
            } catch (\Throwable) {
                // تجاهل
            }

            return back()
                ->withErrors(['error' => 'حدث خطأ أثناء تنفيذ الحجز: ' . $e->getMessage()])
                ->withInput();
        }
    }

    // تم إزالة أي تحسين للصور. يتم التخزين مباشرة على public مع حد للحجم عبر الفاليديشن فقط.

    /**
     * حذف الحجز وجميع الدفعات/الأقساط التابعة له (للمدير فقط عبر الميدل وير)
     */
    public function destroy(Booking $booking)
    {
        $unitId = $booking->unit_id;

        try {
            DB::transaction(function () use ($booking) {
                // أعد حالة الوحدة إلى متاحة
                if ($booking->unit) {
                    $booking->unit->update(['status' => 'available']);
                }

                // حذف الدفعات والأقساط والمستندات المرتبطة
                $booking->payments()->delete();
                $booking->installments()->delete();
                if (\Illuminate\Support\Facades\Schema::hasTable('documents')) {
                    $booking->documents()->delete();
                }

                // حذف الحجز
                $booking->delete();
            });

            // log activity
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('activities')) {
                    \App\Models\Activity::create([
                        'user_id' => optional(request()->user())->id,
                        'action' => 'booking.delete',
                        'entity_type' => Booking::class,
                        'entity_id' => $booking->id,
                        'meta' => [],
                        'ip' => request()->ip(),
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::error('Unhandled exception while logging booking.delete activity', [
                    'location'   => __METHOD__,
                    'class'      => static::class,
                    'booking_id' => $booking->id ?? null,
                    'message'    => $e->getMessage(),
                    'trace'      => $e->getTraceAsString(),
                ]);

                report($e);
            }

            if ($unitId) {
                return redirect()->route('units.show', $unitId)
                    ->with('success', 'تم حذف الحجز وجميع الدفعات/الأقساط التابعة له.');
            }

            return back()->with('success', 'تم حذف الحجز وجميع الدفعات/الأقساط التابعة له.');
        } catch (\Throwable $e) {
            return back()->with('error', 'فشل حذف الحجز: ' . $e->getMessage());
        }
    }
}
