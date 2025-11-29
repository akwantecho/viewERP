<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Installment;
use Illuminate\Support\Facades\Storage;
use App\Models\Unit;
use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\InstallmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class InstallmentController extends Controller
{
    public function __construct(private InstallmentService $installmentService)
    {
    }

    // عرض جميع الأقساط لحجز معين
    public function index(Booking $booking)
    {
        $booking->load([
            'unit.floor.project',
            'customer',

            // كل دفعات الحجز (العربون + دفعات الأقساط)
            'payments' => fn ($q) => $q
                ->select('id','booking_id','installment_id','amount','paid_at','payment_method','bank_name','reference_no','receipt')
                ->latest(),

            // الأقساط مع مجموع المدفوعات لكل قسط
            'installments' => fn ($q) => $q
                ->orderBy('installment_number')
                ->withSum('payments as paid_sum', 'amount'),
        ]);

        $unit = $booking->unit;

        // ملخصات مالية
        $totalPaid             = (float) $booking->payments->sum('amount');
        $advancePaid           = (float) $booking->payments->whereNull('installment_id')->sum('amount');
        $installmentsPaid      = $totalPaid - $advancePaid;
        $remainingOverall      = max(0, (float) $booking->total_price - $totalPaid);
        $remainingInstallments = max(0, (float) $booking->remaining_amount - $installmentsPaid);

        return view('installments.index', [
            'unit'                  => $unit,
            'booking'               => $booking,
            'installments'          => $booking->installments, // يحتوي paid_sum لكل قسط
            'totalPaid'             => $totalPaid,
            'advancePaid'           => $advancePaid,
            'installmentsPaid'      => $installmentsPaid,
            'remainingOverall'      => $remainingOverall,
            'remainingInstallments' => $remainingInstallments,
            'installmentPaid'       => [],
            'latestMeta'            => [],
        ]);
    
}

    public function plan(Booking $booking)
    {
        $booking->load(['unit.floor.project', 'customer', 'installments' => fn ($q) => $q->orderBy('installment_number')]);

        $existingPlan = $booking->installments->map(function ($installment) {
            return [
                'number' => $installment->installment_number,
                'due_date' => optional($installment->due_date)->format('Y-m-d'),
                'total' => round((float) ($installment->total_amount ?? 0), 2),
            ];
        })->values();

        $planDefaults = [
            'plan_type' => $booking->plan_type === 'custom' ? 'custom' : 'fixed',
            'installments_count' => max((int) $booking->installments_count, $booking->installments->count() ?: 12),
            'monthly_due_day' => $booking->monthly_due_day ?? optional($booking->reservation_date)->day ?? 5,
            'installment_frequency' => max(1, (int) ($booking->installment_frequency ?: 1)),
            'start_date' => optional($booking->reservation_date)->format('Y-m-d') ?? now()->format('Y-m-d'),
        ];

        $hasInstallmentPayments = $booking->payments()->whereNotNull('installment_id')->exists();

        return view('installments.plan', [
            'booking' => $booking,
            'unit' => $booking->unit,
            'customer' => $booking->customer,
            'existingPlan' => $existingPlan,
            'planDefaults' => $planDefaults,
            'customDefaults' => $existingPlan->toArray(),
            'remainingAmount' => round((float) ($booking->remaining_amount ?? max($booking->total_price - $booking->advance_payment, 0)), 2),
            'hasInstallmentPayments' => $hasInstallmentPayments,
        ]);
    }

    public function storePlan(Request $request, Booking $booking)
    {
        $planType = $request->input('plan_type');

        $rules = [
            'plan_type' => ['required', Rule::in(['fixed', 'custom'])],
            'start_date' => ['required', 'date'],
            'installment_value' => ['nullable', 'numeric', 'min:0.01'],
        ];

        if ($planType === 'custom') {
            $rules['custom_installments'] = ['required', 'array', 'min:1'];
            $rules['custom_installments.*.due_date'] = ['required', 'date'];
            $rules['custom_installments.*.total_amount'] = ['required', 'numeric', 'min:0.01'];
            $rules['custom_installments.*.number'] = ['nullable', 'integer', 'min:1'];
        } else {
            $rules['installments_count'] = ['required', 'integer', 'min:1', 'max:120'];
            $rules['monthly_due_day'] = ['required', 'integer', 'min:1', 'max:31'];
            $rules['installment_frequency'] = ['required', 'integer', 'min:1', 'max:12'];
        }

        $validated = $request->validate($rules);

        $planType = $validated['plan_type'];

        $remaining = round((float) ($booking->remaining_amount ?? max($booking->total_price - $booking->advance_payment, 0)), 2);
        if ($remaining <= 0) {
            return back()->withErrors(['plan_type' => 'لا يوجد مبلغ متبقٍ لإنشاء خطة أقساط.']);
        }

        $startDate = Carbon::parse($validated['start_date'])->startOfDay();

        $options = [
            'remaining' => $remaining,
            'start_date' => $startDate,
        ];

        if ($planType === 'fixed') {
            $options['installments_count'] = (int) $validated['installments_count'];
            $options['monthly_due_day'] = (int) $validated['monthly_due_day'];
            $options['installment_frequency'] = (int) $validated['installment_frequency'];
            if (!empty($validated['installment_value'])) {
                $options['installment_value'] = round((float) $validated['installment_value'], 2);
            }
        } else {
            $customRows = collect($validated['custom_installments'])->map(function ($row, $index) {
                $total = round((float) ($row['total_amount'] ?? $row['total'] ?? 0), 2);
                return [
                    'number' => (int) ($row['number'] ?? ($index + 1)),
                    'due_date' => $row['due_date'],
                    'total' => $total,
                ];
            })->filter(fn ($row) => !empty($row['due_date']) && $row['total'] > 0)->values();

            if ($customRows->isEmpty()) {
                return back()->withErrors(['custom_installments' => 'يرجى إضافة دفعة واحدة على الأقل.']);
            }

            $options['custom_installments'] = $customRows->toArray();
        }

        try {
            DB::transaction(function () use ($booking, $options, $planType, $validated) {
                $booking->load('installments.payments');

                foreach ($booking->installments as $installment) {
                    $installment->payments()->update(['installment_id' => null]);
                    $installment->delete();
                }

                $this->installmentService->generatePlan($booking, $options);

                $booking->update([
                    'plan_type' => $planType,
                    'installments_count' => $planType === 'fixed'
                        ? (int) $validated['installments_count']
                        : count($options['custom_installments']),
                    'installment_frequency' => $planType === 'fixed'
                        ? (int) $validated['installment_frequency']
                        : 0,
                    'monthly_due_day' => $planType === 'fixed'
                        ? (int) $validated['monthly_due_day']
                        : null,
                ]);
            });
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['custom_installments' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return back()->withErrors(['plan_type' => 'تعذر إنشاء الخطة: ' . $e->getMessage()]);
        }

        return redirect()->route('units.show', $booking->unit_id)
            ->with('success', 'Installment plan created successfully');
    }


    // عرض صفحة دفع القسط
    public function show($id)
    {
        $installment = Installment::with('booking.unit')->findOrFail($id);
        $unit = $installment->booking->unit;

        return view('installments.pay', compact('installment', 'unit'));
    }

    // تنفيذ الدفع
    

    // طباعة قسط منفرد
    public function printInstallment(Installment $installment)
    {
        $installment->load('booking.unit.floor.project', 'booking.customer');
        $booking = $installment->booking;

        return Pdf::loadView('pdf.installment', compact('booking', 'installment'))
            ->setPaper('a4', 'portrait')
            ->stream('installment-invoice.pdf');
    }

    // تقرير الدفعات المدفوعة
    public function printReport($bookingId)
    {
        $booking = Booking::with([
            'unit.floor.project',
            'customer',
            'installments' => function ($q) {
                $q->where('status', 'paid')->orderBy('due_date');
            }
        ])->findOrFail($bookingId);

        return view('installments.report', compact('booking'));
    }

    // فاتورة جميع الأقساط المدفوعة
    public function printInvoice($bookingId)
    {
        $booking = Booking::with([
            'customer',
            'unit.floor.project',
            'installments' => function ($q) {
                $q->where('status', 'paid')->orderBy('installment_number');
            }
        ])->findOrFail($bookingId);

        return Pdf::loadView('invoices.print', compact('booking'))
            ->setPaper('a4', 'portrait')
            ->stream('Installment_Report.pdf');
    }

    // توليد ملف PDF للجدول الزمني للدفعات
    public function schedulePdf(Booking $booking)
    {
        $booking->load([
            'customer',
            'unit.floor.project',
            // جميع دفعات الحجز (لعربون وحساب المجاميع)
            'payments' => fn ($q) => $q
                ->select('id','booking_id','installment_id','amount','paid_at','payment_method','bank_name','reference_no','receipt')
                ->latest(),
            // الأقساط + دفعات كل قسط
            'installments' => fn ($q) => $q
                ->orderBy('installment_number')
                ->with(['payments' => function ($p) {
                    $p->select('id','booking_id','installment_id','amount','paid_at','payment_method','bank_name','reference_no','receipt');
                }]),
        ]);

        return Pdf::loadView('pdf.installments_schedule', [
            'booking' => $booking,
        ])->setPaper('a4', 'portrait')
            ->stream('Payment_Schedule_Booking_' . $booking->id . '.pdf');
    }

    // عرض قسط ضمن وحدة محددة
    public function showForUnit(Unit $unit, Installment $installment)
    {
        if ($installment->booking->unit_id !== $unit->id) {
            abort(403, 'هذا القسط لا يتبع هذه الوحدة.');
        }

        // Reuse the existing pay view for a specific installment
        return redirect()->route('installments.pay', $installment->id);
    }
}
