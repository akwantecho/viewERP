<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Payment;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Concerns\NormalizesReferenceInput;
use App\Services\InstallmentService;
use App\Services\InvoiceNumberGenerator;

class DirectSaleController extends Controller
{
    use NormalizesReferenceInput;

    public function __construct(
        private InstallmentService $installmentService,
        private InvoiceNumberGenerator $invoiceNumbers,
    ) {}

    /**
     * عرض نموذج البيع المباشر لوحدة عقارية.
     */
    public function form(int $id)
    {
        $unit = Unit::with('floor.project')->findOrFail($id);

        if ($unit->status !== 'available') {
            return redirect()
                ->route('units.show', $unit->id)
                ->with('error', 'الوحدة غير متاحة للبيع المباشر.');
        }

        $customers = Customer::orderBy('name')->get();
        $banks = [
            'Bank Muscat','Bank Dhofar','National Bank of Oman','Sohar International',
            'Ahli Bank','Oman Arab Bank','HSBC Oman','Bank Nizwa','Alizz Islamic Bank',
        ];

        return view('sales.create', compact('unit', 'customers', 'banks'));
    }

    /**
     * تنفيذ عملية البيع المباشر للوحدة.
     */
    public function store(Request $request, Unit $unit)
    {
        if ($unit->status !== 'available') {
            return redirect()
                ->route('units.show', $unit->id)
                ->with('error', 'الوحدة غير متاحة للبيع المباشر.');
        }

        $normalizedReference = $this->normalizeReferenceInput($request);
        $request->merge([
            'reference_no' => $normalizedReference !== null ? (string) $normalizedReference : null,
        ]);

        $validated = $request->validate([
            'unit_price'          => ['nullable', 'numeric', 'min:1'],
            'reservation_payment' => ['required', 'numeric', 'min:0'],
            'handover_due_date'   => ['nullable', 'date', 'after_or_equal:today'],
            'customer_id'         => ['required', 'exists:customers,id'],
            'payment_method'      => ['required', 'string', 'max:100'],
            'bank_name'           => ['nullable', 'string', 'max:100'],
            'reference_no'        => ['required', 'string', 'max:100'],
            'receipt'             => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            'sale_date'           => ['required', 'date'],
        ]);

        $vatRate     = (float) config('app.vat_rate', 0.05);
        $basePriceRaw = (float) ($unit->base_price ?? 0);
        if ($basePriceRaw <= 0) {
            $fallbackTotal = (float) ($validated['unit_price'] ?? 0);
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
        $agreedPrice = $agreedBase;
        $totalPrice  = $agreedBase;
        $vatTotal    = $agreedVat;
        $saleDate    = Carbon::parse($validated['sale_date'])->startOfDay();

        $advanceInput = round($validated['reservation_payment'], 2);
        if ($advanceInput > $totalPrice) {
            $advanceInput = $totalPrice;
        }

        $advanceAmount = $advanceInput;
        $handoverAmount = round(max($totalPrice - $advanceAmount, 0), 2);

        $handoverDueRaw = $validated['handover_due_date'] ?? null;
        if ($handoverAmount > 0 && empty($handoverDueRaw)) {
            return back()->withErrors([
                'handover_due_date' => 'يرجى تحديد تاريخ استلام الدفعة النهائية.'
            ])->withInput();
        }

        $handoverDue = ($handoverAmount > 0 && $handoverDueRaw)
            ? Carbon::parse($handoverDueRaw)->startOfDay()
            : null;

        $monthlyDueDay = $handoverDue?->day ?? (int) $saleDate->day;

        $receiptPath = null;
        $receiptUrl  = null;

        if ($request->hasFile('receipt')) {
            try {
                $disk = config('filesystems.disks.s3') ? 's3' : 'public';
                $projectCode = strtoupper((string) optional($unit->floor->project)->code);
                $unitCode    = strtoupper((string) $unit->unit_code);
                $safeProject = preg_replace('/[^A-Za-z0-9\-_.]+/', '-', $projectCode ?: 'PROJECT');
                $safeUnit    = preg_replace('/[^A-Za-z0-9\-_.]+/', '-', $unitCode ?: ('UNIT-'.$unit->id));
                $dir = "sales/{$safeProject}/{$safeUnit}/payments";

                $ext  = strtolower($request->file('receipt')->getClientOriginalExtension() ?: 'pdf');
                $base = $validated['reference_no']
                    ? preg_replace('/[^A-Za-z0-9\-_.]+/', '-', (string) $validated['reference_no'])
                    : ('SALE-'.$unit->id);
                $base = trim($base, '-');
                $nameToUse = $base . '.' . $ext;

                $counter = 1;
                try {
                    while (Storage::disk($disk)->exists("$dir/$nameToUse") && $counter < 50) {
                        $nameToUse = $base . '-' . $counter . '.' . $ext;
                        $counter++;
                    }
                } catch (\Throwable $e) {
                    \Log::error('Unhandled exception while checking existing direct sale receipt', [
                        'location' => __METHOD__,
                        'class'    => static::class,
                        'unit_id'  => $unit->id ?? null,
                        'dir'      => $dir,
                        'message'  => $e->getMessage(),
                        'trace'    => $e->getTraceAsString(),
                    ]);

                    report($e);
                }

                $storedRel = $request->file('receipt')->storePubliclyAs($dir, $nameToUse, $disk);

                if ($disk === 'public') {
                    try {
                        ImageOptimizer::optimize(storage_path('app/public/' . $storedRel));
                    } catch (\Throwable $e) {
                        \Log::error('Unhandled exception while optimizing direct sale receipt image', [
                            'location' => __METHOD__,
                            'class'    => static::class,
                            'unit_id'  => $unit->id ?? null,
                            'path'     => $storedRel,
                            'message'  => $e->getMessage(),
                            'trace'    => $e->getTraceAsString(),
                        ]);

                        report($e);
                    }
                }

                $receiptPath = $storedRel;
                try {
                    $receiptUrl = Storage::disk($disk)->url($storedRel);
                } catch (\Throwable $e) {
                    \Log::error('Unhandled exception while generating direct sale receipt URL', [
                        'location' => __METHOD__,
                        'class'    => static::class,
                        'unit_id'  => $unit->id ?? null,
                        'path'     => $storedRel,
                        'message'  => $e->getMessage(),
                        'trace'    => $e->getTraceAsString(),
                    ]);

                    report($e);

                    $receiptUrl = null;
                }
            } catch (\Throwable $e) {
                \Log::error('Unhandled exception while processing direct sale receipt upload', [
                    'location' => __METHOD__,
                    'class'    => static::class,
                    'unit_id'  => $unit->id ?? null,
                    'message'  => $e->getMessage(),
                    'trace'    => $e->getTraceAsString(),
                ]);

                report($e);

                $receiptPath = null;
                $receiptUrl  = null;
            }
        }

        try {
            DB::beginTransaction();

            $unit = Unit::with('floor.project')->lockForUpdate()->findOrFail($unit->id);

            if ($unit->status !== 'available') {
                DB::rollBack();
                return redirect()
                    ->route('units.show', $unit->id)
                    ->with('error', 'الوحدة لم تعد متاحة للبيع المباشر.');
            }

            $customer = Customer::findOrFail($validated['customer_id']);

            $unit->update([
                'status'      => 'sold',
                'customer_id' => $customer->id,
            ]);

        $installmentsCount = $handoverAmount > 0 ? 1 : 0;

            $booking = Booking::create([
                'unit_id'            => $unit->id,
                'customer_id'        => $customer->id,
                'project_id'         => $unit->floor->project_id,
                'reservation_date'   => $saleDate,
                'unit_price'         => $agreedBase,
                'agreed_base'        => $agreedBase,
                'agreed_vat'         => $agreedVat,
                'agreed_price'       => $agreedPrice,
                'vat'                => $vatTotal,
                'total_price'        => $totalPrice,
                'advance_payment'    => $advanceAmount,
                'advance_amount'     => $advanceAmount,
                'remaining_amount'   => $handoverAmount,
                'installments_count' => $installmentsCount,
                'installment_frequency' => $installmentsCount > 0 ? 1 : 0,
                'monthly_due_day'    => $installmentsCount > 0 ? $monthlyDueDay : null,
                'plan_type'          => 'fixed',
                'status'             => 'confirmed',
            ]);

            $unitCodeForRef = $unit->unit_code ? strtoupper((string) $unit->unit_code) : ('UNIT-' . $unit->id);
            $autoReference  = 'SALE-' . $unitCodeForRef . '-' . now()->format('His');

            if ($advanceAmount > 0) {
                Payment::create([
                    'booking_id'     => $booking->id,
                    'invoice_number' => $this->invoiceNumbers->next(),
                    'installment_id' => null,
                    'amount'         => $advanceAmount,
                    'payment_method' => $validated['payment_method'],
                    'bank_name'      => $validated['bank_name'],
                    'reference_no'   => $validated['reference_no'] ?: $autoReference,
                    'receipt'        => $receiptPath,
                    'paid_at'        => $saleDate,
                ]);
            }

            if ($handoverAmount > 0) {
                $startForPlan = $handoverDue
                    ? $handoverDue->copy()->subMonthNoOverflow()
                    : $saleDate;

                $this->installmentService->generatePlan($booking, [
                    'remaining'             => $handoverAmount,
                    'installments_count'    => 1,
                    'installment_value'     => $handoverAmount,
                    'monthly_due_day'       => $monthlyDueDay,
                    'installment_frequency' => 1,
                    'start_date'            => $startForPlan,
                ]);
            }

            if ($advanceAmount > 0 && $receiptUrl && Schema::hasTable('documents')) {
                Document::create([
                    'name'               => 'Direct Sale Payment Receipt',
                    'type'               => 'payment_receipt',
                    'path'               => $receiptUrl,
                    'documentable_type'  => Booking::class,
                    'documentable_id'    => $booking->id,
                ]);
            }

            try {
                if (Schema::hasTable('activities')) {
                    \App\Models\Activity::create([
                        'user_id'     => optional($request->user())->id,
                        'action'      => 'unit.direct_sale',
                        'entity_type' => Booking::class,
                        'entity_id'   => $booking->id,
                        'meta'        => [
                            'unit_id'     => $unit->id,
                            'total_price' => $totalPrice,
                        ],
                        'ip'          => $request->ip(),
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::error('Unhandled exception while logging unit.direct_sale activity', [
                    'location'   => __METHOD__,
                    'class'      => static::class,
                    'booking_id' => $booking->id ?? null,
                    'unit_id'    => $unit->id ?? null,
                    'message'    => $e->getMessage(),
                    'trace'      => $e->getTraceAsString(),
                ]);

                report($e);
            }

            DB::commit();

            $message = app()->isLocale('ar')
                ? 'تم بيع الوحدة بنجاح، وتسجيل الدفعة الأولى وجدولة دفعة الاستلام.'
                : 'The unit was successfully sold, the first payment was recorded and the delivery payment was scheduled.';

            return redirect()
                ->route('units.show', $unit->id)
                ->with('success', $message);
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withErrors(['error' => 'حدث خطأ أثناء تنفيذ البيع المباشر: ' . $e->getMessage()])
                ->withInput();
        }
    }
}
