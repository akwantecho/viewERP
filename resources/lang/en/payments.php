<?php

return [
    'index' => [
        'title' => 'Payments',
        'subtitle' => ':project · :unit · :customer',
        'buttons' => [
            'installments' => '← Installments',
            'plan' => '⚙️ View Installment Plan',
            'new' => '➕ New payment',
            'print_all' => '🖨️ All payments',
            'delete_booking' => '🗑️ Delete booking',
        ],
        'confirm_booking' => [
            'title' => 'Delete Booking',
            'message' => 'This will permanently delete the booking and all related payments/installments. Continue?',
        ],
        'alerts' => [
            'total' => 'Total value',
            'paid' => 'Paid',
            'remaining' => 'Remaining',
        ],
        'table' => [
            'headers' => [
                'index' => '#',
                'invoice' => 'Invoice',
                'type' => 'Type',
                'amount' => 'Amount',
                'method' => 'Method',
                'reference' => 'Reference',
                'paid_at' => 'Paid at',
                'receipt' => 'Receipt',
                'actions' => 'Actions',
            ],
            'receipt_view' => '📎 View',
            'empty' => 'No payments recorded yet.',
        ],
        'actions' => [
            'view' => 'View',
            'invoice' => 'Open',
            'print' => 'PDF',
            'edit' => 'Edit',
            'delete' => 'Delete',
        ],
        'confirm_payment' => [
            'title' => 'Delete Payment',
            'message' => 'Are you sure you want to delete this payment? This action cannot be undone.',
        ],
        'type_label' => [
            'installment' => 'Installment #:number',
            'advance' => 'Advance / general',
        ],
        'misc' => [
            'currency' => 'OMR',
            'no_bank' => 'No bank',
        ],
    ],
];
