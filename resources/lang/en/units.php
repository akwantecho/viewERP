<?php

return [
    'show' => [
        'badge' => 'Unit',
        'status' => [
            'available' => 'Available',
            'reserved' => 'Reserved',
            'sold' => 'Sold',
            'default' => 'Unknown',
        ],
        'labels' => [
            'project' => 'Project',
            'project_code' => 'Project Code',
            'floor' => 'Floor',
            'updated' => 'Updated',
            'currency' => 'OMR',
            'unknown' => '—',
            'not_available' => 'N/A',
        ],
        'metrics' => [
            'total' => 'Total (incl. VAT)',
            'base' => 'Base Value',
            'vat' => 'VAT',
            'area' => 'Area',
            'area_with_unit' => ':value m²',
        ],
        'summary' => [
            'title' => 'Quick Summary',
            'description' => 'This unit belongs to project :project on floor :floor.',
            'status' => 'Current status: :status.',
        ],
        'actions' => [
            'reserve' => 'Reserve Unit',
            'direct_sale' => 'Direct Sale',
            'base_price' => 'Edit Unit Base Price',
            'details' => 'Unit Details',
        ],
        'specs' => [
            'title' => 'Unit Specifications',
            'description' => 'Update the base price and floor area for accurate reporting.',
            'base_label' => 'Base Price (OMR)',
            'area_label' => 'Area (m²)',
            'submit' => 'Save Specifications',
        ],
        'customer' => [
            'title' => 'Customer Information',
            'description' => 'Details for the customer attached to the active booking.',
            'name' => 'Name',
            'phone' => 'Phone',
            'email' => 'Email',
        ],
        'key_actions' => [
            'title' => 'Key Actions',
            'description' => 'Manage installments, payments, and documentation related to this unit.',
            'installments' => [
                'badge' => 'Installments',
                'description' => 'Open the installment schedule and track settlement progress.',
                'schedule' => 'Schedule',
                'plan' => 'Plan Builder',
            ],
            'payments' => [
                'badge' => 'Payments',
                'description' => 'Review and manage payments associated with this booking.',
                'open' => 'Open',
            ],
            'documents' => [
                'badge' => 'Documents',
                'description' => 'Upload supporting documents and review signed contracts.',
                'open' => 'Open',
            ],
        ],
        'financial' => [
            'title' => 'Financial Snapshot',
            'booking_date' => 'Booking date',
            'total_price' => 'Total price',
            'advance_paid' => 'Advance paid',
            'remaining' => 'Remaining amount',
            'last_update' => 'Last booking update: :date',
        ],
        'notes' => [
            'title' => 'Notes',
            'body' => 'Update the unit status after every sale or cancellation and attach supporting documents for quick reference.',
        ],
        'modal' => [
            'title' => 'Edit Base Price',
            'description' => 'Update the default price used for future bookings of this unit.',
            'label' => 'Base Price (OMR)',
            'cancel' => 'Cancel',
            'submit' => 'Update Price',
        ],
        'messages' => [
            'no_booking' => 'No booking',
        ],
    ],
];
