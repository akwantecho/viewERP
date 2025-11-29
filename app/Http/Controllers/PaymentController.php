<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Installment;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Concerns\NormalizesReferenceInput;
use App\Services\PaymentReceiptService;
use App\Services\InvoiceNumberGenerator;

class PaymentController extends Controller
{
    use NormalizesReferenceInput;

    public function __construct(
        private PaymentReceiptService $receiptService,
        private InvoiceNumberGenerator $invoiceNumbers,
    )
    {
    }

    /**
     * Display all payments (advance and installments) for the booking.
     */
    public function index(Booking $booking)
    {
        // Order: Advance (no installment) first, then by installment number asc, then by paid date asc
        $payments = $booking->payments()
            ->with('installment')
            ->leftJoin('installments', 'payments.installment_id', '=', 'installments.id')
            ->select('payments.*')
            ->orderByRaw('payments.installment_id IS NOT NULL') // 0 (advance) first, then 1
            ->orderBy('installments.installment_number')
            ->orderBy('payments.paid_at')
            ->orderBy('payments.id')
            ->get();
        return view('payments.index', compact('booking', 'payments'));
    }

    /**
     * Show the payment creation form (general or installment payment).
     */
    public function create(Booking $booking, Installment $installment = null)
    {
        $this->ensureInstallmentBelongsToBooking($booking, $installment);

        return view('payments.create', compact('booking', 'installment'));
    }

    /**
     * Store a new payment. Each partial installment payment gets its own record.
     */
    public function store(Request $request, Booking $booking, Installment $installment = null)
    {
        $this->ensureInstallmentBelongsToBooking($booking, $installment);

        $request->merge([
            'reference_no' => $this->normalizeReferenceInput($request),
        ]);

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:100',
            'bank_name'      => 'nullable|string|max:100',
            'reference_no'   => [
                Rule::requiredIf(in_array($request->input('payment_method'), ['bank_transfer', 'cheque'], true)),
                'nullable',
                'string',
                'max:100',
            ],
            'paid_at'        => 'nullable|date',
            'receipt'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $referenceNoForFile = $validated['reference_no'] ?? null;

        // Upload the receipt to S3 or the public disk using the reference-based name
        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $this->receiptService->store(
                $request->file('receipt'),
                $booking,
                $referenceNoForFile
            );
        }

        // Create the payment record (partial installment payments are kept independently)
        $payment = Payment::create([
            'booking_id'     => $booking->id,
            'invoice_number' => $this->invoiceNumbers->next(),
            'installment_id' => $installment?->id, // null represents an advance/general payment
            'amount'         => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'bank_name'      => $validated['bank_name'] ?? null,
            'reference_no'   => $referenceNoForFile,
            'receipt'        => $receiptPath,
            'paid_at'        => $validated['paid_at'] ?? now(),
        ]);

        if ($installment) {
            $this->recomputeInstallment($installment);
        }

        // Log activity (create)
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('activities')) {
                \App\Models\Activity::create([
                    'user_id' => optional($request->user())->id,
                    'action' => 'payment.create',
                    'entity_type' => Payment::class,
                    'entity_id' => $payment->id,
                    'meta' => [
                        'booking_id' => $booking->id,
                        'amount' => $payment->amount,
                        'method' => $payment->payment_method,
                    ],
                    'ip' => $request->ip(),
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('Unhandled exception while logging payment.create activity', [
                'location'   => __METHOD__,
                'class'      => static::class,
                'booking_id' => $booking->id ?? null,
                'payment_id' => $payment->id ?? null,
                'message'    => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            report($e);
        }

        return redirect()
            ->route('payments.index', $booking->id)
            ->with('success', 'Payment recorded successfully.');
    }

    /**
     * Display a single payment.
     */
    public function show(Booking $booking, Payment $payment)
    {
        if ($payment->booking_id !== $booking->id) {
            abort(404);
        }

        $payment->load('installment');
        return view('payments.show', compact('booking', 'payment'));
    }

    /**
     * Display the invoice view in HTML for a single payment.
     */
    public function invoice(Booking $booking, Payment $payment)
    {
        if ($payment->booking_id !== $booking->id) {
            abort(404);
        }

        $payment->load('installment', 'booking.unit.floor.project', 'booking.customer');

        [, , $totalPrice] = $this->resolveBookingTotals($booking);

        $orderedPayments = $booking->payments()
            ->leftJoin('installments', 'payments.installment_id', '=', 'installments.id')
            ->select('payments.*')
            ->orderByRaw('payments.installment_id IS NOT NULL')
            ->orderBy('installments.installment_number')
            ->orderBy('payments.paid_at')
            ->orderBy('payments.id')
            ->get();

        $orderedPayments->load('installment');

        $paidBefore = $orderedPayments
            ->takeWhile(fn (Payment $p) => $p->id !== $payment->id)
            ->sum('amount');

        $previousRemaining = max(0, round($totalPrice - $paidBefore, 2));
        $remainingAfter    = max(0, round($previousRemaining - (float) $payment->amount, 2));

        $receiptUrl = null;
        if (!empty($payment->receipt)) {
            $path = $payment->receipt;
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                $receiptUrl = $path;
            } elseif (Storage::disk('public')->exists($path)) {
                $receiptUrl = Storage::url($path);
            }
        }

        return view('payments.invoice', [
            'booking'            => $booking,
            'payment'            => $payment,
            'previousRemaining'  => $previousRemaining,
            'remainingAfter'     => $remainingAfter,
            'receiptUrl'         => $receiptUrl,
        ]);
    }

    /**
     * Render a PDF for a single payment.
     */
    public function print(Booking $booking, Payment $payment)
    {
        if ($payment->booking_id !== $booking->id) {
            abort(404);
        }

        $payment->load('installment', 'booking.unit.floor.project', 'booking.customer');

        [, , $totalPrice] = $this->resolveBookingTotals($booking);

        $orderedPayments = $booking->payments()
            ->leftJoin('installments', 'payments.installment_id', '=', 'installments.id')
            ->select('payments.*')
            ->orderByRaw('payments.installment_id IS NOT NULL')
            ->orderBy('installments.installment_number')
            ->orderBy('payments.paid_at')
            ->orderBy('payments.id')
            ->get();

        $orderedPayments->load('installment');

        $paidBefore = $orderedPayments
            ->takeWhile(fn (Payment $p) => $p->id !== $payment->id)
            ->sum('amount');

        $previousRemaining = max(0, round($totalPrice - $paidBefore, 2));
        $remainingAfter    = max(0, round($previousRemaining - (float) $payment->amount, 2));

        // Prepare receipt image as data URI if stored on S3 (or public)
        $receiptDataUri = null;
        if (!empty($payment->receipt)) {
            $path = $payment->receipt;
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $imageExts = ['jpg','jpeg','png','webp','gif'];
            if (in_array($ext, $imageExts)) {
                $disk = null;
                if (config('filesystems.disks.s3') && Storage::disk('s3')->exists($path)) {
                    $disk = 's3';
                } elseif (Storage::disk('public')->exists($path)) {
                    $disk = 'public';
                }
                if ($disk) {
                    try {
                        $bytes = Storage::disk($disk)->get($path);
                        $mime = match($ext) {
                            'jpg','jpeg' => 'image/jpeg',
                            'png' => 'image/png',
                            'webp' => 'image/webp',
                            'gif' => 'image/gif',
                            default => 'application/octet-stream',
                        };
                        $receiptDataUri = 'data:' . $mime . ';base64,' . base64_encode($bytes);
                    } catch (\Throwable $e) {
                        \Log::error('Unhandled exception while generating receipt data URI', [
                            'location'    => __METHOD__,
                            'class'       => static::class,
                            'booking_id'  => $booking->id ?? null,
                            'payment_id'  => $payment->id ?? null,
                            'receipt_path'=> $path,
                            'message'     => $e->getMessage(),
                            'trace'       => $e->getTraceAsString(),
                        ]);

                        report($e);
                    }
                }
            }
        }

        return Pdf::view('payments.print', [
            'booking' => $booking,
            'payment' => $payment,
            'receiptDataUri' => $receiptDataUri,
            'previousRemaining' => $previousRemaining,
            'remainingAfter'    => $remainingAfter,
        ])->format('a4')
            ->portrait()
            ->inline('Payment_Receipt_' . $payment->id . '.pdf');
    }

    /**
     * Render a PDF that lists all payments for the booking.
     */
    public function printAll(Booking $booking)
    {
        $booking->load(['unit.floor.project', 'customer']);

        $orderedPayments = $booking->payments()
            ->leftJoin('installments', 'payments.installment_id', '=', 'installments.id')
            ->select('payments.*')
            ->orderByRaw('payments.installment_id IS NOT NULL')
            ->orderBy('installments.installment_number')
            ->orderBy('payments.paid_at')
            ->orderBy('payments.id')
            ->get();

        $running = 0.0;
        [, , $totalPrice] = $this->resolveBookingTotals($booking);

        $paymentsWithRunning = $orderedPayments->map(function (Payment $payment) use (&$running, $totalPrice) {
            $before = max(0, round($totalPrice - $running, 2));
            $running += (float) $payment->amount;
            $after  = max(0, round($totalPrice - $running, 2));

            return [
                'payment'            => $payment,
                'previous_remaining' => $before,
                'remaining_after'    => $after,
            ];
        });

        return Pdf::view('payments.print_all', [
            'booking'  => $booking,
            'payments' => $paymentsWithRunning,
            'generated_at' => now()->format('Y-m-d H:i'),
            'total_price' => $totalPrice,
        ])->format('a4')
            ->portrait()
            ->inline('All_Payments_Booking_' . $booking->id . '.pdf');
    }

    /**
     * Edit a payment
     */
    public function edit(Booking $booking, Payment $payment)
    {
        if ($payment->booking_id !== $booking->id) {
            abort(404);
        }
        return view('payments.edit', compact('booking', 'payment'));
    }

    /**
     * Update a payment
     */
    public function update(Request $request, Booking $booking, Payment $payment)
    {
        if ($payment->booking_id !== $booking->id) {
            abort(404);
        }

        // cache the installment (if any) so we can recompute it after the update
        $installment = $payment->installment;

        $request->merge([
            'reference_no' => $this->normalizeReferenceInput($request),
        ]);

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:100',
            'bank_name'      => 'nullable|string|max:100',
            'reference_no'   => [
                Rule::requiredIf(in_array($request->input('payment_method'), ['bank_transfer', 'cheque'], true)),
                'nullable',
                'string',
                'max:100',
            ],
            'paid_at'        => 'nullable|date',
            'receipt'        => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        $newReceiptPath = null;
        if ($request->hasFile('receipt')) {
            $newReceiptPath = $this->receiptService->store(
                $request->file('receipt'),
                $booking,
                $validated['reference_no'] ?? null,
                $payment->receipt
            );
        }

        $payment->update([
            'amount'         => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'bank_name'      => $validated['bank_name'] ?? null,
            'reference_no'   => $validated['reference_no'] ?? null,
            'paid_at'        => $validated['paid_at'] ?? $payment->paid_at,
            'receipt'        => $newReceiptPath ?? $payment->receipt,
        ]);

        if ($installment) {
            $this->recomputeInstallment($installment);
        }

        // log activity
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('activities')) {
                \App\Models\Activity::create([
                    'user_id' => optional($request->user())->id,
                    'action' => 'payment.update',
                    'entity_type' => Payment::class,
                    'entity_id' => $payment->id,
                    'meta' => [
                        'booking_id' => $booking->id,
                        'amount' => $payment->amount,
                        'method' => $payment->payment_method,
                    ],
                    'ip' => $request->ip(),
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('Unhandled exception while logging payment.update activity', [
                'location'   => __METHOD__,
                'class'      => static::class,
                'booking_id' => $booking->id ?? null,
                'payment_id' => $payment->id ?? null,
                'message'    => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            report($e);
        }

        return redirect()->route('payments.index', $booking->id)
            ->with('success', 'Payment updated successfully.');
    }

    /**
     * Delete a payment.
     */
    public function destroy(Booking $booking, Payment $payment)
    {
        if ($payment->booking_id !== $booking->id) {
            abort(404);
        }

        $installment = $payment->installment; // cache
        $this->receiptService->delete($payment->receipt);
        $payment->delete();

        if ($installment) {
            $this->recomputeInstallment($installment);
        }

        // log activity
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('activities')) {
                \App\Models\Activity::create([
                    'user_id' => optional(request()->user())->id,
                    'action' => 'payment.delete',
                    'entity_type' => Payment::class,
                    'entity_id' => $payment->id,
                    'meta' => [ 'booking_id' => $booking->id ],
                    'ip' => request()->ip(),
                ]);
            }
        } catch (\Throwable $e) {
            \Log::error('Unhandled exception while logging payment.delete activity', [
                'location'   => __METHOD__,
                'class'      => static::class,
                'booking_id' => $booking->id ?? null,
                'payment_id' => $payment->id ?? null,
                'message'    => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            report($e);
        }

        return redirect()->route('payments.index', $booking->id)
            ->with('success', 'Payment deleted.');
    }

    private function ensureInstallmentBelongsToBooking(Booking $booking, ?Installment $installment = null): void
    {
        if ($installment && $installment->booking_id !== $booking->id) {
            abort(404);
        }
    }

    private function recomputeInstallment(Installment $installment): void
    {
        $totalPaid = (float) Payment::where('installment_id', $installment->id)->sum('amount');
        if ($totalPaid >= $installment->total_amount) {
            $installment->status = 'paid';
        } elseif ($totalPaid > 0) {
            $installment->status = 'partial';
        } else {
            $installment->status = 'unpaid';
        }
        $installment->save();
    }

    private function resolveBookingTotals(Booking $booking): array
    {
        $base = (float) ($booking->agreed_base ?? $booking->unit_price ?? 0);
        $vat = (float) ($booking->agreed_vat ?? $booking->vat ?? 0);
        $total = (float) ($booking->agreed_price ?? $booking->total_price ?? ($base + $vat));

        return [$base, $vat, $total];
    }
}
