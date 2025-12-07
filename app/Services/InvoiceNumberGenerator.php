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
        $yearShort = now()->format('y'); // last two digits, e.g. 25 for 2025
        $prefix    = sprintf('INV-%s-', $yearShort);
        $pattern   = sprintf('/^INV-%s-(\d{4,})$/i', $yearShort);

        $latestInvoice = Payment::whereNotNull('invoice_number')
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('invoice_number');

        $latestSequence = 0;
        if ($latestInvoice && preg_match($pattern, $latestInvoice, $matches)) {
            $latestSequence = (int) ($matches[1] ?? 0);
        }

        $nextSequence = max(1, (int) $latestSequence + 1);

        do {
            $candidate = $prefix . str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);
            $nextSequence++;
        } while (Payment::where('invoice_number', $candidate)->exists());

        return strtoupper($candidate);
    }
}
