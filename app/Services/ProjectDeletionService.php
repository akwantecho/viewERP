<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectDeletionService
{
    public function __construct(
        private PaymentReceiptService $receiptService,
    ) {
    }

    /**
     * Delete a project after cleaning up its payments (and receipts).
     *
     * @return array{payments_deleted:int}
     */
    public function delete(Project $project): array
    {
        $deletedPayments = 0;

        DB::transaction(function () use ($project, &$deletedPayments) {
            Booking::where('project_id', $project->id)
                ->select('id')
                ->chunkById(50, function ($bookings) use (&$deletedPayments) {
                    $bookingIds = $bookings->pluck('id');

                    Payment::whereIn('booking_id', $bookingIds)
                        ->select('id', 'receipt')
                        ->chunkById(100, function ($payments) use (&$deletedPayments) {
                            foreach ($payments as $payment) {
                                $this->receiptService->delete($payment->receipt);
                                $payment->delete();
                                $deletedPayments++;
                            }
                        });
                });

            $project->delete();
        });

        return ['payments_deleted' => $deletedPayments];
    }
}
