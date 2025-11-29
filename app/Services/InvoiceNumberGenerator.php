<?php

namespace App\Services;

use App\Models\Payment;

class InvoiceNumberGenerator
{
    /**
     * Generate the next unique invoice number for payments.
     */
    public function next(): string
    {
        $latestInvoice = Payment::whereNotNull('invoice_number')
            ->orderByDesc('id')
            ->value('invoice_number');

        $latestSequence = 0;
        if ($latestInvoice && preg_match('/(\d+)$/', $latestInvoice, $matches)) {
            $latestSequence = (int) $matches[1];
        }

        $nextSequence = max(1, $latestSequence + 1);

        while (true) {
            $candidate = sprintf('INV-%04d', $nextSequence);
            if (!Payment::where('invoice_number', $candidate)->exists()) {
                return $candidate;
            }

            $nextSequence++;
        }
    }
}
